<?php
session_start();
include('conexao.php'); // Incluindo o arquivo de conexão com o banco

$erro = ''; // Variável para armazenar a mensagem de erro

if (isset($_POST['email']) && isset($_POST['senha'])) {

    if (strlen(trim($_POST['email'])) == 0) {
        $erro = "Preencha seu e-mail";
    } elseif (strlen(trim($_POST['senha'])) == 0) {
        $erro = "Preencha sua senha";
    } else {
        // Usando $conn em vez de $mysqli
        $email = $conn->real_escape_string($_POST['email']);
        $senha = $_POST['senha']; // Senha fornecida pelo usuário

        // Consulta SQL para buscar usuário pelo e-mail
        $sql_code = "SELECT id, nome, senha, tipo_usuario FROM usuarios WHERE email = ?"; 
        $stmt = $conn->prepare($sql_code);
        $stmt->bind_param("s", $email); // Usando prepared statements para evitar SQL Injection
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $usuario = $result->fetch_assoc();

            // Verifica se a senha fornecida é válida usando password_verify()
            if (password_verify($senha, $usuario['senha'])) {
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['nome'] = $usuario['nome'];
                $_SESSION['tipo_usuario'] = $usuario['tipo_usuario']; // Armazena o tipo de usuário na sessão

                // Redireciona para a página adequada com base no tipo de usuário
                if (strtolower($_SESSION['tipo_usuario']) == 'adm') {
                    header("Location: PainelPonto.php"); // Redireciona para o painel do administrador
                } else {
                    header("Location: registrar_ponto.php"); // Redireciona para a tela de registro de ponto
                }
                exit();
            } else {
                $erro = "Falha ao logar! E-mail ou senha incorretos"; // Mensagem de erro
            }
        } else {
            $erro = "Falha ao logar! E-mail ou senha incorretos"; // Mensagem de erro
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/Index.css">
    <title>Login</title>
</head>
<body>
    <form action="" method="POST">
        <!-- Exibe a mensagem de erro, caso haja -->
        <?php if (!empty($erro)): ?>
            <div style="color: red; text-align: center; margin-bottom: 15px;"><?php echo $erro; ?></div>
        <?php endif; ?>

        <h1>Acesse sua conta</h1>
        <p>
            <label>E-mail</label>
            <input type="text" name="email" required>
        </p>
        <p>
            <label>Senha</label>
            <input type="password" name="senha" required>
        </p>
        <p>
            <button type="submit">Entrar</button>
        </p>
    </form>
</body>
</html>
