<?php
/**
 * Sistema de Agendamento de Atividades
 * Gerenciamento de atividades acadêmicas
 */

session_start();

// Verificar se o usuário está logado e é professor
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true || $_SESSION["user_type"] !== "professor") {
    header("Location: ../index.php");
    exit();
}

require_once "../inclusos/conexao.php";

$success_message = "";
$error_message = "";

// Classe para gerenciar atividades
class AtividadeManager {
    private $con;
    private $user_id;
    
    public function __construct($connection, $user_id) {
        $this->con = $connection;
        $this->user_id = $user_id;
    }
    
    // Agendar nova atividade
    public function agendarAtividade($dados) {
        try {
            $stmt = $this->con->prepare("
                INSERT INTO atividade (disciplina_id, titulo, descricao, data_atividade, tipo, criado_por) 
                VALUES (:disciplina_id, :titulo, :descricao, :data_atividade, :tipo, :criado_por)
            ");
            
            $stmt->bindParam(":disciplina_id", $dados["disciplina_id"]);
            $stmt->bindParam(":titulo", $dados["titulo"]);
            $stmt->bindParam(":descricao", $dados["descricao"]);
            $stmt->bindParam(":data_atividade", $dados["data_atividade"]);
            $stmt->bindParam(":tipo", $dados["tipo"]);
            $stmt->bindParam(":criado_por", $this->user_id);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Erro ao agendar atividade: " . $e->getMessage());
            return false;
        }
    }
    
    // Obter todas as atividades
    public function obterAtividades() {
        try {
            $stmt = $this->con->prepare("
                SELECT a.*, d.nome as disciplina_nome
                FROM atividade a 
                JOIN disciplina d ON a.disciplina_id = d.id 
                WHERE a.criado_por = :professor_id 
                ORDER BY a.data_atividade ASC
            ");
            
            $stmt->bindParam(":professor_id", $this->user_id);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erro ao obter atividades: " . $e->getMessage());
            return [];
        }
    }
    
    // Obter disciplinas do professor
    public function obterDisciplinas() {
        try {
            $stmt = $this->con->prepare("
                SELECT id, nome
                FROM disciplina 
                WHERE professor_id = :professor_id 
                ORDER BY nome ASC
            ");
            
            $stmt->bindParam(":professor_id", $this->user_id);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erro ao obter disciplinas: " . $e->getMessage());
            return [];
        }
    }
    
    // Excluir atividade
    public function excluirAtividade($id) {
        try {
            $stmt = $this->con->prepare("DELETE FROM atividade WHERE id = :id AND criado_por = :user_id");
            $stmt->bindParam(":id", $id);
            $stmt->bindParam(":user_id", $this->user_id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Erro ao excluir atividade: " . $e->getMessage());
            return false;
        }
    }
}

// Inicializar o gerenciador
$gerenciador = new AtividadeManager($con, $_SESSION["user_id"]);

// Processar requisições AJAX
if (isset($_GET["ajax"])) {
    header("Content-Type: application/json");
    
    if ($_GET["ajax"] === "excluir_atividade" && isset($_POST["id"])) {
        $resultado = $gerenciador->excluirAtividade($_POST["id"]);
        echo json_encode(["success" => $resultado]);
    }
    exit();
}

// Processar formulários
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["action"]) && $_POST["action"] === "agendar_atividade") {
        if ($gerenciador->agendarAtividade($_POST)) {
            $success_message = "Atividade agendada com sucesso!";
        } else {
            $error_message = "Erro ao agendar atividade. Tente novamente.";
        }
    }
}

// Obter dados para exibição
$atividades = $gerenciador->obterAtividades();
$disciplinas = $gerenciador->obterDisciplinas();

// Processar mensagens de URL
if (isset($_GET["message"])) {
    $success_message = htmlspecialchars($_GET["message"]);
}

$con = null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendamento de Atividades - Professor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Variáveis CSS para cores personalizáveis */
        :root {
            --brand-primary: #ff5252;
            --brand-secondary: #D32F2F;
            --brand-accent: #d32f2f;
            
            /* === TEMA CLARO === */
            --primary: var(--brand-primary);
            --secondary: var(--brand-secondary);
            --accent: var(--brand-accent);
            
            /* Backgrounds */
            --background: #f8f9fa;
            --background-secondary: #ffffff;
            --surface: rgba(255, 255, 255, 0.9);
            --surface-elevated: #ffffff;
            
            /* Textos */
            --text-primary: #333333;
            --text-secondary: #5f6368;
            --text-muted: #9aa0a6;
            --text-inverse: #ffffff;
            
            /* Bordas e sombras */
            --border: rgba(0, 0, 0, 0.12);
            --border-light: rgba(0, 0, 0, 0.06);
            --shadow: rgba(0, 0, 0, 0.1);
            --shadow-elevated: rgba(0, 0, 0, 0.15);
            
            /* Efeitos de brilho */
            --glow-primary: 0 0 10px rgba(255, 82, 82, 0.3), 0 0 20px rgba(255, 82, 82, 0.2);
            --glow-secondary: 0 0 10px rgba(211, 47, 47, 0.3), 0 0 20px rgba(211, 47, 47, 0.2);
            
            /* Header */
            --header-bg: var(--surface-elevated);
            --header-text: var(--text-primary);
            --header-border: var(--border);
        }
        
        /* === TEMA ESCURO === */
        [data-theme="dark"] {
            --primary: #ff6b6b;
            --secondary: #f74f4f;
            --accent: #ff5252;
            
            /* Backgrounds escuros */
            --background: #121212;
            --background-secondary: #1e1e1e;
            --surface: rgba(30, 30, 30, 0.9);
            --surface-elevated: #2d2d2d;
            
            /* Textos para dark mode */
            --text-primary: #ffffff;
            --text-secondary: #b3b3b3;
            --text-muted: #8a8a8a;
            --text-inverse: #000000;
            
            /* Bordas e sombras escuras */
            --border: rgba(255, 255, 255, 0.12);
            --border-light: rgba(255, 255, 255, 0.06);
            --shadow: rgba(0, 0, 0, 0.4);
            --shadow-elevated: rgba(0, 0, 0, 0.6);
            
            /* Efeitos de brilho intensificados */
            --glow-primary: 0 0 15px rgba(255, 107, 107, 0.5), 0 0 30px rgba(255, 107, 107, 0.3);
            --glow-secondary: 0 0 15px rgba(247, 79, 79, 0.5), 0 0 30px rgba(247, 79, 79, 0.3);
            
            /* Header escuro */
            --header-bg: var(--surface-elevated);
            --header-text: var(--text-primary);
            --header-border: var(--border);
            
            /* Estados interativos escuros */
            --hover-overlay: rgba(255, 255, 255, 0.08);
            --active-overlay: rgba(255, 255, 255, 0.12);
            --focus-ring: rgba(247, 79, 79, 0.4);
        }
        
        /* ===== RESET E BASE ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: var(--background);
            color: var(--text-primary);
            min-height: 100vh;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            line-height: 1.6;
        }

        /* Estilos Gerais para a Página de Agendamento */
        .container-principal {
            max-width: 1200px;
            margin: 80px auto 40px auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .agenda-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 20px;
            background: var(--surface-elevated);
            border-radius: 15px;
            box-shadow: 0 4px 20px var(--shadow-elevated);
            border: 1px solid var(--border);
        }

        .agenda-title {
            font-size: 2.8rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 10px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            display: inline-block;
        }

        .agenda-title i {
            margin-right: 15px;
            color: var(--primary);
        }

        .agenda-subtitle {
            font-size: 1.2rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
        }

        /* Layout em Grid */
        .agenda-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
        }

        @media (min-width: 992px) {
            .agenda-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        /* Cards */
        .form-card, .list-card {
            background: var(--surface);
            border-radius: 15px;
            box-shadow: 0 2px 10px var(--shadow);
            border: 1px solid var(--border);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .form-card:hover, .list-card:hover {
            box-shadow: 0 4px 20px var(--shadow-elevated);
            transform: translateY(-3px);
        }

        .form-header, .list-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: 20px;
            color: var(--text-inverse);
            text-align: center;
            border-bottom: 1px solid var(--border);
        }

        .form-title, .list-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .form-title i, .list-title i {
            font-size: 1.5rem;
        }

        .form-body {
            padding: 30px;
        }

        /* Formulários Modernos */
        .modern-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group label i {
            color: var(--accent);
        }

        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--background-secondary);
            color: var(--text-primary);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="date"]:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 82, 82, 0.2);
            outline: none;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--primary), var(--brand-secondary));
            color: var(--text-inverse);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            width: 100%;
        }

        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        /* Listas de Eventos */
        .list-body {
            padding: 20px;
        }

        .event-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .event-list li {
            background: var(--background-secondary);
            border: 1px solid var(--border-light);
            border-radius: 10px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
            position: relative;
        }

        .event-list li:hover {
            box-shadow: 0 2px 10px var(--shadow);
            transform: translateY(-2px);
        }

        .event-date {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--primary);
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .event-date i {
            color: var(--accent);
        }

        .event-details {
            flex-grow: 1;
        }

        .event-details strong {
            display: block;
            font-size: 1.1rem;
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .event-details p {
            font-size: 0.95rem;
            color: var(--text-secondary);
            margin: 0 0 5px 0;
        }

        .event-meta {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 5px;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            background: var(--brand-accent);
            color: var(--text-inverse);
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .disciplina-tag {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            background: var(--primary);
            color: var(--text-inverse);
            font-size: 0.8rem;
            font-weight: 600;
        }

        .empty-list {
            text-align: center;
            color: var(--text-muted);
            padding: 40px 20px;
            font-style: italic;
        }

        .empty-list i {
            font-size: 3rem;
            margin-bottom: 10px;
            display: block;
            opacity: 0.3;
        }

        .delete-form {
            margin-left: auto;
        }

        .btn-delete {
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 1.1rem;
            transition: color 0.3s ease;
            padding: 8px;
        }

        .btn-delete:hover {
            color: var(--brand-primary);
        }

        /* Alertas */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background-color: #e6ffed;
            color: #1a7f37;
            border: 1px solid #b2dfc8;
        }

        .alert-danger {
            background-color: #ffe6e6;
            color: #d73a49;
            border: 1px solid #f8c8c8;
        }

        /* Dark Mode Adjustments */
        [data-theme="dark"] .alert-success {
            background-color: #1a3622;
            color: #6ee7b7;
            border-color: #34d399;
        }

        [data-theme="dark"] .alert-danger {
            background-color: #4a1a1d;
            color: #fca5a5;
            border-color: #ef4444;
        }

        /* Responsividade */
        @media (max-width: 991px) {
            .agenda-title {
                font-size: 2rem;
            }

            .container-principal {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <?php require_once "../inclusos/cabecalho.php"; ?>

    <main class="container-principal">
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="agenda-grid">
            <!-- Formulário de Nova Atividade -->
            <div class="form-column">
                <div class="form-card">
                    <div class="form-header">
                        <h2 class="form-title"><i class="fas fa-plus-circle"></i> Nova Atividade</h2>
                    </div>
                    <div class="form-body">
                        <form method="POST" action="agendamento.php" class="modern-form">
                            <input type="hidden" name="action" value="agendar_atividade">
                            
                            <div class="form-group">
                                <label for="disciplina_id"><i class="fas fa-book"></i> Disciplina</label>
                                <select id="disciplina_id" name="disciplina_id" required>
                                    <option value="">Selecione uma disciplina</option>
                                    <?php foreach ($disciplinas as $disciplina): ?>
                                        <option value="<?php echo $disciplina['id']; ?>">
                                            <?php echo htmlspecialchars($disciplina['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="titulo"><i class="fas fa-heading"></i> Título da Atividade</label>
                                <input type="text" id="titulo" name="titulo" placeholder="Ex: Prova de Matemática" required>
                            </div>

                            <div class="form-group">
                                <label for="descricao"><i class="fas fa-align-left"></i> Descrição</label>
                                <textarea id="descricao" name="descricao" rows="4" placeholder="Descreva os detalhes da atividade..." required></textarea>
                            </div>

                            <div class="form-group">
                                <label for="data_atividade"><i class="fas fa-calendar-check"></i> Data de Entrega</label>
                                <input type="date" id="data_atividade" name="data_atividade" required>
                            </div>

                            <div class="form-group">
                                <label for="tipo"><i class="fas fa-tag"></i> Tipo de Atividade</label>
                                <select id="tipo" name="tipo" required>
                                    <option value="trabalho">Trabalho</option>
                                    <option value="prova">Prova</option>
                                    <option value="exercicio">Exercício</option>
                                    <option value="redacao">Redação</option>
                                    <option value="relatorio">Relatório</option>
                                    <option value="atividade">Atividade</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Salvar Atividade
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Lista de Atividades -->
            <div class="list-column">
                <div class="list-card">
                    <div class="list-header">
                        <h3 class="list-title"><i class="fas fa-clipboard-list"></i> Atividades Agendadas</h3>
                    </div>
                    <div class="list-body">
                        <?php if (empty($atividades)): ?>
                            <div class="empty-list">
                                <i class="fas fa-inbox"></i>
                                <p>Nenhuma atividade agendada.</p>
                            </div>
                        <?php else: ?>
                            <ul class="event-list">
                                <?php foreach ($atividades as $atividade): ?>
                                    <li>
                                        <div class="event-date">
                                            <i class="fas fa-calendar"></i> 
                                            <?php echo date("d/m/Y", strtotime($atividade["data_atividade"])); ?>
                                        </div>
                                        <div class="event-details">
                                            <strong><?php echo htmlspecialchars($atividade["titulo"]); ?></strong>
                                            <p><?php echo htmlspecialchars($atividade["descricao"]); ?></p>
                                            <div class="event-meta">
                                                <span class="disciplina-tag">
                                                    <?php echo htmlspecialchars($atividade["disciplina_nome"]); ?>
                                                </span>
                                                <span class="badge">
                                                    <?php echo htmlspecialchars($atividade["tipo"]); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <form method="POST" action="agendamento.php?ajax=excluir_atividade" class="delete-form">
                                            <input type="hidden" name="id" value="<?php echo $atividade["id"]; ?>">
                                            <button type="submit" class="btn-delete" title="Excluir atividade">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Configurar data mínima para hoje
            const dataInput = document.getElementById('data_atividade');
            const hoje = new Date().toISOString().split('T')[0];
            dataInput.setAttribute('min', hoje);

            // Handler para exclusão de atividades
            document.querySelectorAll('.delete-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    if (confirm('Tem certeza que deseja excluir esta atividade?')) {
                        const formData = new FormData(this);
                        const actionUrl = this.action;

                        fetch(actionUrl, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                this.closest('li').remove();
                                
                                // Verificar se a lista ficou vazia
                                const lista = document.querySelector('.event-list');
                                if (lista && lista.children.length === 0) {
                                    lista.parentElement.innerHTML = `
                                        <div class="empty-list">
                                            <i class="fas fa-inbox"></i>
                                            <p>Nenhuma atividade agendada.</p>
                                        </div>
                                    `;
                                }
                            } else {
                                alert('Erro ao excluir atividade.');
                            }
                        })
                        .catch(error => {
                            console.error('Erro:', error);
                            alert('Erro na comunicação com o servidor.');
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>