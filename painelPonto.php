<?php
include 'conexao.php'; // Inclui o arquivo de conexão com o banco

// Verifica se a sessão já está ativa antes de iniciá-la
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// Função para buscar registros de ponto com filtro por e-mail
function buscarPontos($conn, $data_inicial, $data_final, $modelo, $email_funcionario = null) {
    // Query base
    $query = "SELECT hora, tipo FROM $modelo WHERE DATE(hora) BETWEEN ? AND ?";

    // Se um e-mail for informado, filtra corretamente
    if (!empty($email_funcionario)) {
        $query .= " AND usuario_id = (SELECT id FROM usuarios WHERE email = ? LIMIT 1)";
    }

    $query .= " ORDER BY hora ASC";

    // Prepara a consulta
    $stmt = $conn->prepare($query);

    // Define os parâmetros corretamente
    if (!empty($email_funcionario)) {
        $stmt->bind_param("sss", $data_inicial, $data_final, $email_funcionario);
    } else {
        $stmt->bind_param("ss", $data_inicial, $data_final);
    }

    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Usuário</title>
    <style>
        /* Reset de estilos */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #a3cfe2, #6fa3d6);
            height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background-size: 400% 400%;
            animation: gradientAnimation 20s ease infinite;
            padding-top: 60px;
        }

        /* Estilização da barra de navegação */
        nav {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background-color: #3b9f8f;
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        nav h2 {
            margin-left: 20px;
            font-size: 24px;
        }

        nav ul {
            list-style: none;
            display: flex;
            margin-right: 20px;
        }

        nav ul li {
            margin: 0 10px;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s;
        }

        nav ul li a:hover {
            color: #d1f7f5;
        }

        .menu-toggle {
            display: none;
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
        }

        .menu-toggle .bar {
            width: 25px;
            height: 3px;
            background-color: white;
            border-radius: 2px;
        }

        /* Adiciona responsividade */
        @media (max-width: 768px) {
            nav ul {
                display: none;
                flex-direction: column;
                width: 100%;
                background-color: #3b9f8f;
                padding: 10px 0;
                position: absolute;
                top: 60px;
                left: 0;
            }

            nav ul li {
                margin: 10px 0;
                text-align: center;
            }

            .menu-toggle {
                display: flex;
            }

            nav ul.active {
                display: flex;
            }
        }

        .acompanhamento {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            width: 80%;
            max-width: 800px;
            margin-top: 20px;
            text-align: center;
        }

        h3 {
            color: #2f4f4f;
            font-size: 22px;
            margin-bottom: 20px;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        form label {
            font-size: 16px;
            color: #4e7c7b;
            text-align: left;
            font-weight: bold;
        }

        select, input, button {
            padding: 12px;
            border-radius: 10px;
            border: 2px solid #b4d3d2;
            font-size: 16px;
            background-color: #f4faff;
            transition: all 0.3s ease-in-out;
        }

        select:focus, input:focus {
            border-color: #6fa3d6;
            background-color: #fff;
            outline: none;
        }

        button {
            background-color: #3b9f8f;
            color: white;
            font-size: 18px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #2a756b;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        table, th, td {
            border: 1px solid #ccc;
        }

        th, td {
            padding: 12px;
            text-align: center;
        }

        th {
            background-color: #3b9f8f;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        /* Animação do fundo */
        @keyframes gradientAnimation {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }

        /* Ajustes de responsividade */
        @media (max-width: 480px) {
            .acompanhamento {
                width: 95%;
                padding: 15px;
            }
        }
    </style>

</head>
<body>
    <nav>
        <h2>Ponto Eletrônico</h2>
        <div class="menu-toggle" onclick="toggleMenu()">
            <div class="bar"></div>
            <div class="bar"></div>
            <div class="bar"></div>
        </div>
        <ul>
            <li><a href="acompanhamento.php">Acompanhamento</a></li>
            <li><a href="ajustarPonto.php">Ajuste</a></li>
            <li><a href="login.php">Cadastrar</a></li>
            <li><a href="logout.php">Sair</a></li>
        </ul>
    </nav>
    
    <div class="acompanhamento">
        <h3>Registro de Ponto</h3>
        
        <form method="POST">
            <label>Modelo de Ponto:
                <select name="modelo">
                    <option value="horas_efetivas">Horas Efetivas</option>
                    <option value="horas_passe">Horas Passe</option>
                    <option value="sobreaviso">Sobreaviso</option>
                    <option value="prontidao">Prontidão</option>
                </select>
            </label>
            <br>
            <label>Data Inicial: <input type="date" name="data_inicial" required></label>
            <label>Data Final: <input type="date" name="data_final" required></label>
            <br>
            <label>Funcionário:
                <select name="email_funcionario">
                    <option value="">Todos os funcionários</option>
                    <?php
                    $query = "SELECT email, nome FROM usuarios ORDER BY nome ASC";
                    $result = $conn->query($query);
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='{$row['email']}'>{$row['nome']} - {$row['email']}</option>";
                    }
                    ?>
                </select>
            </label>
            <button type="submit" name="buscar">Buscar</button>
        </form>
        
        <?php if (isset($_POST['buscar'])): ?>
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Pegando os dados do formulário
                    $modelo = $_POST['modelo'] ?? 'horas_efetivas';
                    $email_funcionario = $_POST['email_funcionario'] ?? '';

                    // Verificando se as datas estão preenchidas corretamente
                    if (!empty($_POST['data_inicial']) && !empty($_POST['data_final'])) {
                        // Buscar os registros do modelo selecionado
                        $registros = buscarPontos($conn, $_POST['data_inicial'], $_POST['data_final'], $modelo, $email_funcionario);
                        
                        if (empty($registros)) {
                            echo "<tr><td colspan='2'>Nenhum registro encontrado.</td></tr>";
                        } else {
                            foreach ($registros as $registro) {
                                echo "<tr><td>{$registro['hora']}</td><td>{$registro['tipo']}</td></tr>";
                            }
                        }
                    } else {
                        echo "<tr><td colspan='2'>Por favor, preencha as datas para buscar.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <script>
        function toggleMenu() {
            const menu = document.querySelector('nav ul');
            menu.classList.toggle('active');
        }
    </script>
</body>
</html>
