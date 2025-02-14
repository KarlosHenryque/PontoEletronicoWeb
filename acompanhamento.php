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

// Função para buscar todos os funcionários cadastrados
function buscarFuncionarios($conn) {
    $query = "SELECT id, nome, email, tipo_usuario FROM usuarios ORDER BY nome ASC";
    return $conn->query($query);
}

// Função para apagar funcionário
if (isset($_GET['apagar_id'])) {
    $id = $_GET['apagar_id'];
    $query = "DELETE FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo "<script>alert('Funcionário apagado com sucesso!'); window.location.href='acompanhamento.php';</script>";
    } else {
        echo "<script>alert('Erro ao apagar o funcionário!'); window.location.href='acompanhamento.php';</script>";
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Acompanhamento</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            text-align: center;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 90%;
            max-width: 900px;
            margin: 50px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        h3 {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }
        th {
            background: #3b9f8f;
            color: white;
        }
        tr:hover {
            background: #f1f1f1;
        }
        .action-buttons a {
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 5px;
            font-weight: bold;
        }
        .edit-btn {
            background: #f3b61f;
            color: white;
        }
        .edit-btn:hover {
            background: #d9a017;
        }
        .delete-btn {
            background: #e74c3c;
            color: white;
        }
        .delete-btn:hover {
            background: #c0392b;
        }
        
        /* Estilo do botão Voltar */
        .btn-back {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 15px;
            background: #3b9f8f;
            color: white;
            text-decoration: none;
            font-weight: bold;
            border-radius: 6px;
            transition: background 0.3s, transform 0.2s;
        }
        .btn-back:hover {
            background: #2a756b;
            transform: scale(1.05);
        }
    </style>
    <script>
        function confirmarExclusao(id) {
            if (confirm("Tem certeza que deseja excluir este funcionário?")) {
                window.location.href = "acompanhamento.php?apagar_id=" + id;
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <h3>Lista de Funcionários</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Tipo de Usuário</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $funcionarios = buscarFuncionarios($conn);
                while ($funcionario = $funcionarios->fetch_assoc()) {
                    echo "<tr>
                            <td>{$funcionario['id']}</td>
                            <td>{$funcionario['nome']}</td>
                            <td>{$funcionario['email']}</td>
                            <td>{$funcionario['tipo_usuario']}</td>
                            <td class='action-buttons'>
                                <a href='editarFuncionario.php?id={$funcionario['id']}' class='edit-btn'>Editar</a>
                                <a href='#' onclick='confirmarExclusao({$funcionario['id']})' class='delete-btn'>Apagar</a>
                            </td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Botão Voltar aprimorado -->
        <a href="painelPonto.php" class="btn-back">Voltar</a>
    </div>
</body>
</html>
