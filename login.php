<?php
session_start();
include('conexao.php'); // Incluindo o arquivo de conexão com o banco

if (isset($_POST['email']) && isset($_POST['senha'])) {

    if (strlen(trim($_POST['email'])) == 0) {
        echo "Preencha seu e-mail";
    } elseif (strlen(trim($_POST['senha'])) == 0) {
        echo "Preencha sua senha";
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
                if ($_SESSION['tipo_usuario'] == 'adm') {
                    header("Location: PainelPonto.php"); // Redireciona para o painel do administrador
                } else {
                    header("Location: registrar_ponto.php"); // Redireciona para a tela de registro de ponto
                }
                exit();
            } else {
                
            }
        } else {
            
        }
    }
}

// Definindo a variável $sucesso
$sucesso = "";
$erro = "";

// Se o formulário for enviado para cadastrar um novo usuário
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nome'])) {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    $tipo_usuario = $_POST['tipo_usuario'];

    // Valida se as senhas coincidem
    if ($senha != $confirmar_senha) {
        $erro = "As senhas não coincidem!";
    } else {
        // Criptografa a senha
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        // Verifica se o email já está cadastrado
        $sql_check_email = "SELECT id FROM usuarios WHERE email = ?";
        $stmt_check_email = $conn->prepare($sql_check_email);
        $stmt_check_email->bind_param("s", $email);
        $stmt_check_email->execute();
        $result_check_email = $stmt_check_email->get_result();

        if ($result_check_email->num_rows > 0) {
            $erro = "E-mail já cadastrado!";
        } else {
            // Insere o novo usuário no banco de dados
            $sql_insert = "INSERT INTO usuarios (nome, email, senha, tipo_usuario) VALUES (?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("ssss", $nome, $email, $senha_hash, $tipo_usuario);
            if ($stmt_insert->execute()) {
                $sucesso = "Usuário cadastrado com sucesso!";
            } else {
                $erro = "Erro ao cadastrar usuário!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Usuário</title>
    <style>
       /* Reset de estilos */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Estilo geral do body */
body {
    font-family: 'Arial', sans-serif;
    background-color: #f0f4f7;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

/* Estilo do contêiner do formulário */
.form-container {
    background-color: #ffffff;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    width: 350px;
    max-width: 90%;
}

/* Título do formulário */
.form-container h2 {
    font-size: 24px;
    color: #3b9f8f;
    text-align: center;
    margin-bottom: 20px;
}

/* Estilo dos campos de entrada */
.form-container input, .form-container select {
    width: 100%;
    padding: 12px;
    margin: 10px 0;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 16px;
    color: #333;
    transition: border 0.3s ease;
}

/* Foco nos campos de entrada */
.form-container input:focus, .form-container select:focus {
    border-color: #3b9f8f;
    outline: none;
}

/* Estilo do botão de cadastro */
.form-container button {
    width: 100%;
    padding: 12px;
    background-color: #3b9f8f;
    color: white;
    font-size: 18px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

/* Hover no botão de cadastro */
.form-container button:hover {
    background-color: #2a756b;
}

/* Estilo das mensagens de erro e sucesso */
.erro, .sucesso {
    font-size: 16px;
    text-align: center;
    padding: 8px;
    margin-bottom: 15px;
    border-radius: 8px;
}

/* Estilo da mensagem de erro */
.erro {
    background-color: #ffebeb;
    color: #d9534f;
    border: 1px solid #d9534f;
}

/* Estilo da mensagem de sucesso */
.sucesso {
    background-color: #eaf7ea;
    color: #5cb85c;
    border: 1px solid #5cb85c;
}

/* Estilo do botão de voltar */
.voltar-btn {
    background-color: #f1f8f7;
    color: #3b9f8f;
    border: 2px solid #3b9f8f;
    margin-top: 20px;
    padding: 12px;
    font-size: 16px;
    width: 100%;
    cursor: pointer;
    border-radius: 8px;
    transition: background-color 0.3s ease;
}

/* Hover no botão de voltar */
.voltar-btn:hover {
    background-color: #c5e8e2;
}

/* Estilo do campo de tipo de usuário */
.tipo-usuario {
    margin-top: 10px;
}

.tipo-usuario select {
    width: 100%;
    padding: 12px;
    font-size: 16px;
    border: 1px solid #ddd;
    border-radius: 8px;
}

/* Estilo do label */
label {
    font-size: 16px;
    color: #333;
    display: block;
    margin-bottom: 5px;
    text-align: left;
}

    </style>
</head>
<body>

    <div class="form-container">
        <h2>Cadastro</h2>
        
        <!-- Exibe a mensagem de erro, caso haja -->
        <?php if (!empty($erro)): ?>
            <div class="erro"><?php echo $erro; ?></div>
        <?php endif; ?>
        
        <!-- Exibe a mensagem de sucesso, caso o cadastro tenha sido realizado -->
        <?php if (!empty($sucesso)): ?>
            <div class="sucesso"><?php echo $sucesso; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="text" name="nome" placeholder="Nome" required>
            <input type="email" name="email" placeholder="E-mail" required>
            <input type="password" name="senha" placeholder="Senha" required>
            <input type="password" name="confirmar_senha" placeholder="Confirmar Senha" required>
            
            <!-- Campo para escolher o tipo de usuário -->
            <div class="tipo-usuario">
                <label for="tipo_usuario">Tipo de usuário:</label>
                <select name="tipo_usuario" required>
                    <option value="adm">Administrador</option>
                    <option value="colaborador">Colaborador</option>
                </select>
            </div>
            
            <button type="submit">Cadastrar</button>
        </form>
        
        <!-- Botão de Voltar -->
        <a href="painelPonto.php"><button class="voltar-btn">Voltar para o Painel</button></a>
    </div>

</body>
</html>
