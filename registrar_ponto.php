<?php
session_start();
include('conexao.php'); // Incluindo o arquivo de conexão com o banco

// Define o fuso horário para o Brasil (Horário de Brasília)
date_default_timezone_set('America/Sao_Paulo');

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$id_usuario = $_SESSION['usuario_id'];
$query = $conn->prepare("SELECT nome FROM usuarios WHERE id = ?");
$query->bind_param("i", $id_usuario);
$query->execute();
$result = $query->get_result();
$usuario = $result->fetch_assoc();

// Registrar entrada ou saída
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['acao'])) {
        $acao = $_POST['acao']; // "entrada" ou "saida"
        $tipo_ponto = $_POST['tipo_ponto']; // Tipo de ponto selecionado
        $hora_atual = date('Y-m-d H:i:s');

        // Verificar se a ação é de entrada ou saída
        if ($acao == 'entrada' || $acao == 'saida') {
            // Dependendo do tipo de ponto, insere na tabela correspondente
            switch ($tipo_ponto) {
                case 'horas_efetivas':
                    $stmt = $conn->prepare("INSERT INTO horas_efetivas (usuario_id, tipo, hora) VALUES (?, ?, ?)");
                    break;
                case 'horas_passe':
                    $stmt = $conn->prepare("INSERT INTO horas_passe (usuario_id, tipo, hora) VALUES (?, ?, ?)");
                    break;
                case 'sobreaviso':
                    $stmt = $conn->prepare("INSERT INTO sobreaviso (usuario_id, tipo, hora) VALUES (?, ?, ?)");
                    break;
                case 'prontidao':
                    $stmt = $conn->prepare("INSERT INTO prontidao (usuario_id, tipo, hora) VALUES (?, ?, ?)");
                    break;
                default:
                    die("Tipo de ponto inválido.");
            }

            // Vincula os parâmetros e executa
            $stmt->bind_param("iss", $id_usuario, $acao, $hora_atual);
            if ($stmt->execute()) {
                $_SESSION['mensagem_sucesso'] = "Ponto registrado com sucesso!";
            } else {
                $_SESSION['mensagem_erro'] = "Erro ao registrar ponto: " . $conn->error;
            }
        }
    }
}

// Destruição da sessão e redirecionamento para a página index.php
if (isset($_GET['sair'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/registrar_ponto.css">
    <title>Registrar Ponto</title>
    <style>
        /* Reset de estilos */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #a3cfe2, #6fa3d6);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-size: 400% 400%;
            animation: gradientAnimation 20s ease infinite;
            padding: 0 15px; /* Adicionando espaçamento nas laterais */
        }

        /* Estilização do menu sanduíche */
        .menu-toggle {
            display: none;
        }

        .menu-icon {
            display: flex;
            flex-direction: column;
            justify-content: space-around;
            width: 35px;
            height: 30px;
            cursor: pointer;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1100;
        }

        .menu-icon .line {
            height: 4px;
            width: 35px;
            background-color: #fff;
            border-radius: 5px;
            transition: 0.3s ease-in-out;
        }

        /* Overlay escuro quando o menu está aberto */
        .menu-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            display: none;
            z-index: 999;
        }

        /* Menu Lateral */
        .side-menu {
            position: fixed;
            top: 0;
            left: -250px;
            width: 250px;
            height: 100%;
            background-color: #3b9f8f;
            color: white;
            transition: left 0.3s ease-in-out;
            padding-top: 60px;
            box-shadow: 2px 0px 10px rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .side-menu ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        .side-menu li {
            padding: 15px;
            text-align: center;
            transition: background-color 0.3s ease;
        }

        .side-menu li a {
            color: white;
            text-decoration: none;
            font-size: 18px;
            display: block;
        }

        .side-menu li:hover {
            background-color: #2a756b;
        }

        /* Ativar o menu ao clicar */
        .menu-toggle:checked ~ .side-menu {
            left: 0;
        }

        .menu-toggle:checked ~ .menu-overlay {
            display: block;
        }

        /* Animação do botão sanduíche */
        .menu-toggle:checked + .menu-icon .line:nth-child(1) {
            transform: translateY(9px) rotate(45deg);
        }

        .menu-toggle:checked + .menu-icon .line:nth-child(2) {
            opacity: 0;
        }

        .menu-toggle:checked + .menu-icon .line:nth-child(3) {
            transform: translateY(-9px) rotate(-45deg);
        }

        /* Container do conteúdo */
        .container {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            margin-top: 80px;
            position: relative;
        }

        /* Estilo do título */
        h1 {
            font-size: 30px;
            color: #2f4f4f;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        /* Estilo do campo de seleção */
        label {
            font-size: 14px;
            color: #4e7c7b;
            text-align: left;
            display: block;
            margin-bottom: 10px;
        }

        select {
            width: 100%;
            padding: 15px;
            margin: 10px 0;
            border-radius: 10px;
            border: 2px solid #b4d3d2;
            font-size: 16px;
            background-color: #f4faff;
            transition: all 0.3s ease-in-out;
        }

        select:focus {
            border-color: #6fa3d6;
            background-color: #fff;
            outline: none;
        }

        /* Botões */
        button {
            width: 100%;
            padding: 15px;
            background-color: #3b9f8f;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 10px;
        }

        button:hover {
            background-color: #2a756b;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .container {
                padding: 20px;
                margin-top: 20px;
                width: 90%;
            }

            .menu-icon {
                left: 15px;
                top: 10px;
            }

            .side-menu {
                width: 200px;
            }

            .menu-toggle:checked + .menu-icon .line {
                width: 30px;
            }

            button {
                padding: 12px;
            }

            select {
                padding: 12px;
            }
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

        /* Estilo para mensagens de sucesso e erro */
        .mensagem-sucesso {
            background-color: #32CD32;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .mensagem-erro {
            background-color: #FF6347;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Menu Sanduíche -->
    <nav>
        <input type="checkbox" id="menu-toggle" class="menu-toggle">
        <label for="menu-toggle" class="menu-icon">
            <span class="line"></span>
            <span class="line"></span>
            <span class="line"></span>
        </label>

        <div class="menu-overlay" id="menu-overlay"></div>

        <div class="side-menu" id="side-menu">
            <ul>
                <li><a href="?sair=true">Sair</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <?php if (isset($_SESSION['mensagem_sucesso'])): ?>
            <div class="mensagem-sucesso">
                <?php echo $_SESSION['mensagem_sucesso']; ?>
            </div>
            <?php unset($_SESSION['mensagem_sucesso']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['mensagem_erro'])): ?>
            <div class="mensagem-erro">
                <?php echo $_SESSION['mensagem_erro']; ?>
            </div>
            <?php unset($_SESSION['mensagem_erro']); ?>
        <?php endif; ?>

        <h1>Bem-vindo, <?php echo htmlspecialchars($usuario['nome']); ?></h1>
        <p>Data e Hora: <?php echo date('H:i:s | d-m-Y '); ?></p>

        <form method="POST">
            <label for="tipo_ponto">Selecione o tipo de ponto:</label>
            <select name="tipo_ponto" id="tipo_ponto" required>
                <option value="horas_efetivas">Horas Efetivas</option>
                <option value="horas_passe">Horas Passe</option>
                <option value="sobreaviso">Sobreaviso</option>
                <option value="prontidao">Prontidão</option>
            </select>
            <br><br>

            <button type="submit" name="acao" value="entrada">Registrar Entrada</button>
            <button type="submit" name="acao" value="saida">Registrar Saída</button>
        </form>

        <form action="relatorio_ponto.php" method="GET">
            <button type="submit">Ver Relatório de Ponto</button>
        </form>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const menuToggle = document.getElementById("menu-toggle");
            const sideMenu = document.getElementById("side-menu");
            const overlay = document.getElementById("menu-overlay");

            menuToggle.addEventListener("change", function() {
                sideMenu.classList.toggle("open", menuToggle.checked);
                overlay.classList.toggle("open", menuToggle.checked);
            });

            overlay.addEventListener("click", function() {
                menuToggle.checked = false;
                sideMenu.classList.remove("open");
                overlay.classList.remove("open");
            });
        });
    </script>
</body>
</html>
