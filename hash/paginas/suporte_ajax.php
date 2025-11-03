<?php
/**
 * AJAX Handler para gerenciamento de contatos de suporte
 * Sistema Educacional - TCC
 */

session_start();

// Verificar se o usuário está logado e é coordenador
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'coordenador') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit();
}

require_once '../inclusos/conexao.php';

// Verificar se é uma requisição AJAX
if (!isset($_POST['ajax']) || $_POST['ajax'] !== '1') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Requisição inválida']);
    exit();
}

header('Content-Type: application/json');

try {
    switch ($_POST['action']) {
		        case 'create':
		            $secao_exibicao = $_POST['secao_exibicao'];
		            $tipo = trim($_POST['tipo']);
	            $titulo = trim($_POST['titulo']);
	            $valor = trim($_POST['valor']);
	            $icone = $_POST['icone'];
	            $cor = $_POST['cor'];
	            $ordem = (int)$_POST['ordem'];
	            
		            // Validações
		            if (empty($tipo) || empty($titulo) || empty($valor) || empty($icone)) {
	                echo json_encode(['success' => false, 'message' => 'Todos os campos obrigatórios devem ser preenchidos']);
	                exit();
	            }
	            
	            // Verificar se o tipo já existe
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM contato_suporte WHERE tipo = ?");
            $stmt->execute([$tipo]);
            
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'Já existe um contato com este tipo/identificador']);
                exit();
            }
            
	            // Inserir novo contato
	            $stmt = $pdo->prepare("
	                INSERT INTO contato_suporte (tipo, secao_exibicao, titulo, valor, icone, cor, ordem, ativo) 
	                VALUES (?, ?, ?, ?, ?, ?, ?, 1)
	            ");
	            $stmt->execute([$tipo, $secao_exibicao, $titulo, $valor, $icone, $cor, $ordem]);
            
            echo json_encode(['success' => true, 'message' => 'Contato criado com sucesso']);
            break;
            
		        case 'update':
		            $id = (int)$_POST['id'];
		            $secao_exibicao = $_POST['secao_exibicao'];
		            $tipo = trim($_POST['tipo']);
	            $titulo = trim($_POST['titulo']);
	            $valor = trim($_POST['valor']);
	            $icone = $_POST['icone'];
	            $cor = $_POST['cor'];
	            $ordem = (int)$_POST['ordem'];
	            
		            // Validações
		            if (empty($tipo) || empty($titulo) || empty($valor) || empty($icone)) {
	                echo json_encode(['success' => false, 'message' => 'Todos os campos obrigatórios devem ser preenchidos']);
	                exit();
	            }
	            
	            // Verificar se o tipo já existe (exceto para o próprio contato)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM contato_suporte WHERE tipo = ? AND id != ?");
            $stmt->execute([$tipo, $id]);
            
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'Já existe um contato com este tipo/identificador']);
                exit();
            }
            
	            // Atualizar contato
	            $stmt = $pdo->prepare("
	                UPDATE contato_suporte 
	                SET tipo = ?, secao_exibicao = ?, titulo = ?, valor = ?, icone = ?, cor = ?, ordem = ? 
	                WHERE id = ?
	            ");
	            $stmt->execute([$tipo, $secao_exibicao, $titulo, $valor, $icone, $cor, $ordem, $id]);
            
            echo json_encode(['success' => true, 'message' => 'Contato atualizado com sucesso']);
            break;
            
        case 'delete':
            $id = (int)$_POST['id'];
            
            // Deletar contato
            $stmt = $pdo->prepare("DELETE FROM contato_suporte WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Contato deletado com sucesso']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Contato não encontrado']);
            }
            break;
            
	        case 'get_contato':
	            $id = (int)$_POST['id'];
	            
	            $stmt = $pdo->prepare("SELECT * FROM contato_suporte WHERE id = ?");
	            $stmt->execute([$id]);
	            $contato = $stmt->fetch(PDO::FETCH_ASSOC);
	            
	            if ($contato) {
		                // Não é mais necessário manipular o campo 'tipo' para edição, pois ele é armazenado 'limpo' no banco de dados.
		                // A coluna `secao_exibicao` já está no SELECT * e será usada no frontend.
		                
		                echo json_encode(['success' => true, 'contato' => $contato]);
	            } else {
	                echo json_encode(['success' => false, 'message' => 'Contato não encontrado']);
	            }
	            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Ação não reconhecida']);
            break;
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
}
?>