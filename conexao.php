<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start(); // Inicia a sessão apenas se ainda não estiver iniciada
}

$usuario = 'root';
$senha = '';
$database = 'pontoEletronico';
$host = 'localhost';

// Conexão com o banco de dados
$conn = new mysqli($host, $usuario, $senha, $database);

// Verifica se houve erro na conexão
if ($conn->connect_error) {
    die("Falha na conexão com o banco de dados: " . $conn->connect_error);
}
?>
