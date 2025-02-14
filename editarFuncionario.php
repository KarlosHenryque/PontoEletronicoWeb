<?php
include 'conexao.php';

// Verifica se a sessão está ativa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// Verifica se o ID foi passado pela URL
if (!isset($_GET['id'])) {
    echo "Funcionário não encontrado!";
    exit();
}

$id = $_GET['id'];

// Busca os dados do funcionário pelo ID
$query = "SELECT email FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$funcionario = $result->fetch_assoc();

// Se não encontrar o funcionário, exibe erro
if (!$funcionario) {
    echo "Funcionário não encontrado!";
    exit();
}

// Atualiza os dados caso o formulário seja enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $novo_email = $_POST['email'];
    $nova_senha = password_hash($_POST['senha'], PASSWORD_DEFAULT); // Hash da senha para segurança

    $query = "UPDATE usuarios SET email = ?, senha = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssi", $novo_email, $nova_senha, $id);

    if ($stmt->execute()) {
        echo "<script>alert('Dados atualizados com sucesso!'); window.location.href='acompanhamento.php';</script>";
    } else {
        echo "<script>alert('Erro ao atualizar os dados!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Funcionário</title>
    <style>
        /* Estilo geral */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Arial", sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #6fa3d6, #4a90e2);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            padding: 20px;
        }

        /* Container do formulário */
        .form-container {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 350px;
            text-align: center;
            animation: fadeIn 0.6s ease-in-out;
        }

        /* Título */
        h2 {
            margin-bottom: 15px;
            color: #333;
            font-size: 22px;
            font-weight: bold;
        }

        /* Labels */
        label {
            display: block;
            font-weight: bold;
            margin-top: 12px;
            text-align: left;
            font-size: 14px;
            color: #555;
        }

        /* Inputs */
        input {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            transition: border 0.3s;
            font-size: 14px;
        }

        input:focus {
            border: 1px solid #4a90e2;
            outline: none;
        }

        /* Botão salvar */
        .btn {
            background: #3b9f8f;
            color: white;
            padding: 12px;
            border: none;
            width: 100%;
            margin-top: 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s, transform 0.2s;
        }

        .btn:hover {
            background: #2a756b;
            transform: scale(1.05);
        }

        /* Botão voltar */
        .back-btn {
            display: block;
            margin-top: 15px;
            text-decoration: none;
            color: #3b9f8f;
            font-weight: bold;
            transition: color 0.3s;
        }

        .back-btn:hover {
            color: #2a756b;
        }

        /* Animação de entrada */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsividade */
        @media (max-width: 400px) {
            .form-container {
                width: 90%;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Editar Funcionário</h2>
        <form method="POST">
            <label for="email">Novo E-mail:</label>
            <input type="email" name="email" id="email" value="<?= htmlspecialchars($funcionario['email']); ?>" required>

            <label for="senha">Nova Senha:</label>
            <input type="password" name="senha" id="senha" required>

            <button type="submit" class="btn">Salvar Alterações</button>
        </form>
        <a href="acompanhamento.php" class="back-btn">Voltar</a>
    </div>
</body>
</html>
