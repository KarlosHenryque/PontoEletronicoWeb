<?php
session_start();
include('conexao.php');

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// Busca todos os usuários cadastrados
$query_usuarios = "SELECT id, nome FROM usuarios";
$result_usuarios = $conn->query($query_usuarios);

// Tipos de ponto disponíveis
$tabelas_ponto = [
    'horas_efetivas' => 'Horas Efetivas',
    'horas_passe' => 'Horas Passe',
    'sobreaviso' => 'Sobreaviso',
    'prontidao' => 'Prontidão'
];

// Verifica se foi enviada uma solicitação para atualizar a hora e data
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['usuario_id'], $_POST['hora_id'], $_POST['nova_hora'], $_POST['nova_data'], $_POST['tipo_ponto'])) {
    $usuario_id = $_POST['usuario_id'];
    $hora_id = $_POST['hora_id'];
    $nova_hora = $_POST['nova_hora'];
    $nova_data = $_POST['nova_data'];
    $tipo_ponto = $_POST['tipo_ponto'];

    if (array_key_exists($tipo_ponto, $tabelas_ponto)) {
        $nova_data_hora = $nova_data . ' ' . $nova_hora;
        $update_query = "UPDATE $tipo_ponto SET hora = ? WHERE id = ? AND usuario_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("sii", $nova_data_hora, $hora_id, $usuario_id);
        
        if ($update_stmt->execute()) {
            $mensagem_sucesso = "Ponto atualizado com sucesso!";
        } else {
            $mensagem_erro = "Erro ao atualizar o ponto.";
        }
    }
}

// Exibe os pontos apenas se for uma solicitação GET válida
$pontos = [];
if ($_SERVER['REQUEST_METHOD'] == 'GET' || isset($_POST['usuario_id'])) {
    $usuario_id = $_GET['usuario_id'] ?? $_POST['usuario_id'] ?? '';
    $mes_filtro = $_GET['mes_filtro'] ?? $_POST['mes_filtro'] ?? date('Y-m');
    $tipo_ponto = $_GET['tipo_ponto'] ?? $_POST['tipo_ponto'] ?? '';

    if ($usuario_id && $tipo_ponto && array_key_exists($tipo_ponto, $tabelas_ponto)) {
        $query_pontos = "SELECT * FROM $tipo_ponto WHERE usuario_id = ? AND DATE_FORMAT(hora, '%Y-%m') = ?";
        $stmt_pontos = $conn->prepare($query_pontos);
        $stmt_pontos->bind_param("is", $usuario_id, $mes_filtro);
        $stmt_pontos->execute();
        $pontos = $stmt_pontos->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Ajustar Ponto</title>
    <style>
       /* Reseta os estilos padrões */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Estilo do corpo da página */
body {
    font-family: 'Arial', sans-serif;
    background: linear-gradient(135deg, #a3cfe2, #6fa3d6); /* Gradiente suave de azul claro */
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background-size: 400% 400%;
    animation: gradientAnimation 20s ease infinite; /* Animação suave de fundo */
    color: #333;
}

.container {
    background-color: rgba(255, 255, 255, 0.95); /* Fundo branco com leve transparência */
    border-radius: 15px;
    padding: 40px;
    width: 600px;
    max-width: 90%;
    text-align: center;
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
    backdrop-filter: blur(10px); /* Desfoque suave no fundo */
    position: relative; /* Deixando o container fluir normalmente */
    margin-top: 50px;
    margin-bottom: 50px; /* Ajusta a margem inferior */
    max-height: 90vh; /* Definindo a altura máxima como 80% da altura da tela */
    overflow-y: auto; /* Permite rolagem caso o conteúdo ultrapasse a altura máxima */
    border-radius: 10px;
}



/* Estilo do título */
h1 {
    font-size: 30px;
    color: #2f4f4f; /* Verde escuro */
    margin-bottom: 20px;
    text-transform: uppercase;
    letter-spacing: 2px;
    text-align: center;
}

/* Estilo do formulário */
form {
    background-color: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

/* Estilo dos campos de entrada */
label {
    font-size: 14px;
    color: #4e7c7b; /* Verde médio */
    text-align: left;
    display: block;
    margin-bottom: 8px;
}

/* Estilo dos campos de texto e senha */
select,
input[type="text"],
input[type="password"],
input[type="date"],
input[type="month"],
input[type="time"],
button {
    width: 100%;
    padding: 12px;
    margin: 10px 0;
    border-radius: 8px;
    border: 2px solid #b4d3d2; /* Azul suave */
    font-size: 16px;
    background-color: #f4faff; /* Tom suave de azul claro */
    transition: all 0.3s ease-in-out;
}

/* Efeito de foco nos campos de entrada */
select:focus,
input[type="text"]:focus,
input[type="password"]:focus,
input[type="date"]:focus,
input[type="month"]:focus,
input[type="time"]:focus {
    border-color: #6fa3d6; /* Azul mais forte no foco */
    background-color: #fff;
    outline: none;
}

/* Estilo do botão */
button {
    background-color: #3b9f8f; /* Verde suave */
    color: white;
    border: none;
    cursor: pointer;
    transition: background-color 0.3s ease;
    font-weight: bold;
}

button:hover {
    background-color: #2a756b; /* Verde mais escuro para hover */
}

/* Tabela de pontos */
.pontos-container {
    max-height: 300px; /* Limita a altura da tabela */
    overflow-y: auto; /* Adiciona rolagem vertical se necessário */
    margin-top: 20px;
    padding-right: 15px;
    margin-left: 20px;  /* Adiciona margem à esquerda */
    margin-right: 20px; /* Adiciona margem à direita */
}

/* Tabela com limite de largura */
table {
    width: 100%;
    max-width: 90%; /* Limita a largura máxima da tabela */
    margin: 0 auto; /* Centraliza a tabela */
    border-collapse: collapse;
    margin-top: 20px;
    margin-bottom: 30px;
}
table, th, td {
    border: 1px solid #ddd;
}

th, td {
    padding: 12px;
    text-align: center;
}

/* Cabeçalho da tabela */
th {
    background-color: #6fa3d6; /* Azul suave para cabeçalho */
    color: white;
}

/* Mensagens de erro ou sucesso */
p {
    font-size: 16px;
    margin: 20px 0;
}

p.green {
    color: green;
}

p.red {
    color: red;
}

/* Menu */
.menu {
    text-align: center;
    margin-top: 30px;
}

.menu a {
    color: white;
    font-size: 16px;
    text-decoration: none;
    margin: 0 15px;
    font-weight: bold;
}

.menu a:hover {
    text-decoration: underline;
}

/* Botão voltar */
.voltar-btn {
    background-color: #d9534f;
    font-weight: bold;
    padding: 10px 20px;
    text-align: center;
    border-radius: 5px;
    margin-top: 20px;
    display: inline-block;
}

.voltar-btn:hover {
    background-color: #c1392b;
}

/* Animação de fundo */
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

    </style>
</head>
<body>

    <div class="container">
        <h1>Ajustar Ponto</h1>

        <form method="GET">
            <label for="usuario_id">Usuário:</label>
            <select name="usuario_id" required>
                <option value="">Selecione um usuário</option>
                <?php foreach ($result_usuarios as $usuario): ?>
                    <option value="<?php echo $usuario['id']; ?>" <?php echo ($usuario_id == $usuario['id']) ? 'selected' : ''; ?>>
                        <?php echo $usuario['nome']; ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="mes_filtro">Mês:</label>
            <input type="month" name="mes_filtro" value="<?php echo $mes_filtro; ?>" required>
            
            <label for="tipo_ponto">Tipo de Ponto:</label>
            <select name="tipo_ponto" required>
                <?php foreach ($tabelas_ponto as $key => $label): ?>
                    <option value="<?php echo $key; ?>" <?php echo ($tipo_ponto == $key) ? 'selected' : ''; ?>>
                        <?php echo $label; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit">Filtrar</button>
        </form>

        <?php if (isset($mensagem_sucesso)): ?>
            <p class="green"><?php echo $mensagem_sucesso; ?></p>
        <?php elseif (isset($mensagem_erro)): ?>
            <p class="red"><?php echo $mensagem_erro; ?></p>
        <?php endif; ?>

        <?php if (!empty($pontos)): ?>
            <div class="pontos-container">
                <table>
                    <tr>
                        <th>Data</th>
                        <th>Hora</th>
                        <th>Tipo</th>
                        <th>Alterar</th>
                    </tr>
                    <?php foreach ($pontos as $ponto): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($ponto['hora'])); ?></td>
                            <td><?php echo date('H:i', strtotime($ponto['hora'])); ?></td>
                            <td><?php echo $tabelas_ponto[$tipo_ponto]; ?></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="usuario_id" value="<?php echo $usuario_id; ?>">
                                    <input type="hidden" name="hora_id" value="<?php echo $ponto['id']; ?>">
                                    <input type="hidden" name="tipo_ponto" value="<?php echo $tipo_ponto; ?>">
                                    <input type="date" name="nova_data" value="<?php echo date('Y-m-d', strtotime($ponto['hora'])); ?>" required>
                                    <input type="time" name="nova_hora" value="<?php echo date('H:i', strtotime($ponto['hora'])); ?>" required>
                                    <button type="submit">Atualizar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <form method="GET" action="gerar_pdf.php">
            <input type="hidden" name="usuario_id" value="<?php echo $usuario_id; ?>">
            <input type="hidden" name="mes_filtro" value="<?php echo $mes_filtro; ?>">
            <input type="hidden" name="tipo_ponto" value="<?php echo $tipo_ponto; ?>">
            <button type="submit">Gerar PDF</button>
             </form>
        <?php endif; ?>
        <div class="menu">
        <a href="painelPonto.php" class="voltar-btn">Voltar</a>
        </div>
    </div>

</body>
</html>
