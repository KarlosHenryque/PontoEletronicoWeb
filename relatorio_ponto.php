<?php 
session_start(); 
include('conexao.php');  

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {     
    header("Location: login.php");     
    exit(); 
}  

$id_usuario = $_SESSION['usuario_id'];  

// Verifica o tipo de ponto selecionado
$tipo_ponto = $_GET['tipo_ponto'] ?? 'horas_efetivas';  

// Verifica a data selecionada
$data_filtro = $_GET['data_filtro'] ?? '';  

// Define a tabela baseada no tipo de ponto
$tipos_ponto = [     
    'horas_efetivas' => 'horas_efetivas',     
    'horas_passe' => 'horas_passe',     
    'sobreaviso' => 'sobreaviso',     
    'prontidao' => 'prontidao' 
];  

$table = $tipos_ponto[$tipo_ponto] ?? die("Tipo de ponto inválido.");  

// Prepara a consulta
$query_str = "SELECT * FROM $table WHERE usuario_id = ?";
if (!empty($data_filtro)) {
    $query_str .= " AND DATE(hora) = ?";
}

// Prepara a consulta SQL
$query = $conn->prepare($query_str);
if (!empty($data_filtro)) {
    $query->bind_param("is", $id_usuario, $data_filtro); 
} else {
    $query->bind_param("i", $id_usuario); 
}
$query->execute(); 
$result = $query->get_result(); 

// Verifica se há uma solicitação de atualização da hora e data
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hora_id']) && isset($_POST['nova_hora']) && isset($_POST['nova_data'])) {
    $hora_id = $_POST['hora_id'];
    $nova_hora = $_POST['nova_hora'];
    $nova_data = $_POST['nova_data'];

    if (!empty($nova_hora) && !empty($nova_data)) {
        $nova_data_hora = $nova_data . ' ' . $nova_hora;
        $update_query = "UPDATE $table SET hora = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $nova_data_hora, $hora_id);
        $update_stmt->execute();

        header("Location: ".$_SERVER['PHP_SELF']."?tipo_ponto=".$tipo_ponto."&data_filtro=".$data_filtro);
        exit();
    } else {
        echo "Data ou hora inválida.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Ponto</title>
    <style>
        /* Estilos gerais */
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
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 800px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        h1 {
            font-size: 24px;
            color: #2f4f4f;
            margin-bottom: 20px;
        }

        /* Formulário */
        .form-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            width: 100%;
        }

        .form-container select, 
        .form-container input[type="date"] {
            width: 50%;
            padding: 12px;
            font-size: 16px;
            border-radius: 8px;
            border: 2px solid #6fa3d6;
            background: white;
            cursor: pointer;
        }

        button {
            padding: 10px 20px;
            background: #3b9f8f;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #2a756b;
        }

        /* Tabela */
        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: center;
        }

        th {
            background-color: #6fa3d6;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f7f7f7;
        }

        .relatorio-btn {
    margin-top: 20px;
    padding: 10px 20px;
    background: #3b9f8f;
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    cursor: pointer;
    display: inline-block;
    text-decoration: none;
    transition: background-color 0.3s ease;
    text-align: center;
}

.relatorio-btn:hover {
    background: #2a756b;
}
    </style>
</head>
<body>

    <div class="container">
        <h1>Relatório de Ponto</h1>

        <!-- Formulário de filtro -->
        <form action="" method="GET" class="form-container">
            <label for="tipo_ponto">Tipo de Ponto:</label>
            <select name="tipo_ponto" id="tipo_ponto">
                <?php foreach ($tipos_ponto as $key => $value): ?>
                    <option value="<?php echo $key; ?>" <?php echo ($key == $tipo_ponto) ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $key)); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="data_filtro">Data:</label>
            <input type="date" name="data_filtro" value="<?php echo $data_filtro; ?>">

            <button type="submit">Filtrar</button>
        </form>

        <!-- Tabela de registros -->
        <table>
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Data</th>
                    <th>Hora</th>
                    <th>Entrada/Saída</th>
                    <th>Alterar Hora</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                    while ($row = $result->fetch_assoc()) { 
                        $data = date('d/m/Y', strtotime($row['hora']));
                        $hora = date('H:i', strtotime($row['hora']));
                        $tipo_registro = ucfirst($row['tipo']); // Pegando a coluna "tipo" (entrada/saída)
                ?>
                    <tr>
                        <td><?php echo ucfirst($tipo_ponto); ?></td>
                        <td><?php echo $data; ?></td>
                        <td><?php echo $hora; ?></td>
                        <td><?php echo $tipo_registro; ?></td>
                        <td>
                            <form action="" method="POST">
                                <input type="hidden" name="hora_id" value="<?php echo $row['id']; ?>">
                                <input type="date" name="nova_data" value="<?php echo date('Y-m-d', strtotime($row['hora'])); ?>" required>
                                <input type="time" name="nova_hora" value="<?php echo date('H:i', strtotime($row['hora'])); ?>" required>
                                <button type="submit" class="salvar">Salvar</button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <a href="registrar_ponto.php" class="relatorio-btn">Voltar</a>
    </div>

</body>
</html>
