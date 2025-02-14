<?php
require('fpdf/fpdf.php');
include('conexao.php');

// Função para remover acentos sem usar utf8_decode()
function removerAcentos($texto) {
    $mapa = [
        'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c',
        'Á' => 'A', 'À' => 'A', 'Ã' => 'A', 'Â' => 'A', 'Ä' => 'A',
        'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
        'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'Ó' => 'O', 'Ò' => 'O', 'Õ' => 'O', 'Ô' => 'O', 'Ö' => 'O',
        'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
        'Ç' => 'C'
    ];
    return strtr($texto, $mapa);
}

// Tipos de ponto disponíveis
$tabelas_ponto = [
    'horas_efetivas' => 'Horas Efetivas',
    'horas_passe' => 'Horas Passe',
    'sobreaviso' => 'Sobreaviso',
    'prontidao' => 'Prontidão'
];

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['usuario_id'], $_POST['mes_filtro'], $_POST['tipo_ponto_pdf'])) {
    $usuario_id = $_POST['usuario_id'];
    $mes_filtro = $_POST['mes_filtro'];
    $tipo_ponto = $_POST['tipo_ponto_pdf'];

    if (!array_key_exists($tipo_ponto, $tabelas_ponto)) {
        die("Erro: Tipo de ponto inválido.");
    }

    // Criar PDF
    $pdf = new FPDF();
    $pdf->AddPage();

    // Título principal
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, removerAcentos('Relatório de Ponto'), 0, 1, 'C');

    // Buscar nome do usuário
    $query_usuario = "SELECT nome FROM usuarios WHERE id = ?";
    $stmt_usuario = $conn->prepare($query_usuario);
    $stmt_usuario->bind_param("i", $usuario_id);
    $stmt_usuario->execute();
    $result_usuario = $stmt_usuario->get_result();
    
    if ($result_usuario->num_rows > 0) {
        $usuario = $result_usuario->fetch_assoc();
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 10, removerAcentos('Funcionário: ' . $usuario['nome']), 0, 1, 'L');
    } else {
        die("Erro: Usuário não encontrado.");
    }

    // Exibir o tipo de ponto no PDF
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, removerAcentos('Tipo de Ponto: ' . $tabelas_ponto[$tipo_ponto]), 0, 1, 'L');

    // Buscar registros do tipo de ponto selecionado
    $query_pontos = "SELECT * FROM $tipo_ponto WHERE usuario_id = ? AND DATE_FORMAT(hora, '%Y-%m') = ?";
    $stmt_pontos = $conn->prepare($query_pontos);
    $stmt_pontos->bind_param("is", $usuario_id, $mes_filtro);
    $stmt_pontos->execute();
    $result_pontos = $stmt_pontos->get_result();

    // Criar cabeçalho da tabela
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(40, 10, removerAcentos('Data'), 1);
    $pdf->Cell(40, 10, removerAcentos('Hora'), 1);
    $pdf->Cell(40, 10, removerAcentos('Tipo'), 1);
    $pdf->Ln();

    // Preencher tabela com os registros
    $pdf->SetFont('Arial', '', 12);
    while ($ponto = $result_pontos->fetch_assoc()) {
        $pdf->Cell(40, 10, removerAcentos(date('d/m/Y', strtotime($ponto['hora']))), 1);
        $pdf->Cell(40, 10, removerAcentos(date('H:i', strtotime($ponto['hora']))), 1);
        
        // Determinar tipo de ponto (Entrada ou Saída)
        $tipo_ponto_desc = ($ponto['tipo'] == 'entrada') ? 'Entrada' : 'Saída';

        $pdf->Cell(40, 10, removerAcentos($tipo_ponto_desc), 1);
        $pdf->Ln();
    }

    // Rodapé
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'I', 12);
    $pdf->Cell(0, 10, removerAcentos('PontoEletronico/PR'), 0, 1, 'C');

    // Gerar o PDF
    $pdf->Output('D', 'Relatorio_Ponto_' . removerAcentos($usuario['nome']) . '.pdf');
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerar Relatório de Ponto</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #c3cfe2, #6fa3d6); /* Gradiente suave de azul claro */
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #333;
        }

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

        .container {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            padding: 40px;
            width: 600px;
            max-width: 90%;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            position: relative;
            z-index: 10;
        }

        h1 {
            font-size: 28px;
            color: #3b9f8f;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-align: center;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        label {
            font-size: 14px;
            color: #4e7c7b;
            text-align: left;
            font-weight: bold;
        }

        select,
        input[type="month"],
        button {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border-radius: 8px;
            border: 2px solid #b4d3d2;
            font-size: 16px;
            background-color: #f4faff;
            transition: all 0.3s ease-in-out;
        }

        select:focus,
        input[type="month"]:focus {
            border-color: #6fa3d6;
            background-color: #fff;
            outline: none;
        }

        button {
            background-color: #3b9f8f;
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-size: 16px;
        }

        button:hover {
            background-color: #2a756b;
        }

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
        <h1>Gerar Relatório de Ponto</h1>

        <!-- Formulário para selecionar funcionário, mês e tipo de ponto -->
        <form action="gerar_pdf.php" method="POST">
            <label for="usuario_id">Selecione o Funcionário:</label>
            <select name="usuario_id" id="usuario_id" required>
                <option value="">Selecione</option>
                <?php
                $query_usuarios = "SELECT id, nome FROM usuarios";
                $result_usuarios = $conn->query($query_usuarios);
                while ($usuario = $result_usuarios->fetch_assoc()) {
                    echo "<option value='{$usuario['id']}'>{$usuario['nome']}</option>";
                }
                ?>
            </select>

            <label for="mes_filtro">Selecione o Mês:</label>
            <input type="month" name="mes_filtro" id="mes_filtro" required>

            <label for="tipo_ponto_pdf">Tipo de Ponto:</label>
            <select name="tipo_ponto_pdf" id="tipo_ponto_pdf" required>
                <?php foreach ($tabelas_ponto as $key => $label): ?>
                    <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit" name="gerar_pdf">Gerar PDF</button>
        </form>

        <div class="menu">
        <a href="ajustarPonto.php" class="voltar-btn">Voltar</a>
        </div>
    </div>

</body>
</html>