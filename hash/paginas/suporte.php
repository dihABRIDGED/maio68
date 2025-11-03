<?php


session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

require_once '../inclusos/conexao.php';

$user_type = $_SESSION['user_type'] ?? 'aluno';
$username = $_SESSION['username'] ?? 'Usuário';


try {
    $stmt = $pdo->prepare("
        SELECT id, tipo, secao_exibicao, titulo, valor, icone, cor, ordem 
        FROM contato_suporte 
        WHERE ativo = 1 
        ORDER BY ordem ASC, titulo ASC
    ");
    $stmt->execute();
    $contatos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    

    $contatos_organizados = [
        'institucionais' => [],
        'professores' => [],
        'sociais' => []
    ];
    
    foreach ($contatos as $contato) {
        $secao = $contato['secao_exibicao'] ?? 'institucionais';
        if (isset($contatos_organizados[$secao])) {
            $contatos_organizados[$secao][] = $contato;
        } else {
       
            $contatos_organizados['institucionais'][] = $contato;
        }
    }
    
} catch (PDOException $e) {
    $contatos_organizados = [
        'institucionais' => [],
        'professores' => [],
        'sociais' => []
    ];
    $error_message = "Erro ao carregar contatos: " . $e->getMessage();
}
?>

<?php include '../inclusos/cabecalho.php'; ?>

<style>
/* Importa a fonte Inter (se não estiver no header.php) */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

:root {
    /* Cores Principais (Adaptadas do modern.css) */
    --primary-red: #DC3545;
    --primary-red-hover: #C82333;
    --primary-red-light: rgba(220, 53, 69, 0.1);
    
    /* Cores Neutras (Adaptadas do modern.css) */
    --white: #FFFFFF;
    --gray-50: #F8F9FA;
    --gray-100: #E9ECEF;
    --gray-200: #DEE2E6;
    --gray-300: #CED4DA;
    --gray-500: #6C757D;
    --gray-700: #343A40;
    --gray-900: #000000;
    
    /* Espaçamento (Adaptado do modern.css) */
    --spacing-2: 0.5rem;
    --spacing-3: 0.75rem;
    --spacing-4: 1rem;
    --spacing-6: 1.5rem;
    --spacing-8: 2rem;
    
    /* Tipografia (Adaptada do modern.css) */
    --font-family: 'Inter', sans-serif;
    --font-size-base: 1rem;
    --font-size-lg: 1.125rem;
    --font-size-xl: 1.25rem;
    --font-size-2xl: 1.5rem;
    --font-size-3xl: 1.875rem;
    
    /* Bordas e Sombras (Adaptadas do modern.css) */
    --border-radius: 0.375rem;
    --border-radius-lg: 0.5rem;
    --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --transition-fast: 150ms ease-in-out;
    --transition-normal: 300ms ease-in-out;
}

body {
    font-family: var(--font-family);
    background-color: var(--gray-50);
    color: var(--gray-700);
}

/* Estilos para a página de Suporte */
.suporte-container {
    max-width: 1200px;
    margin: var(--spacing-8) auto;
    padding: 0 var(--spacing-6);
}

.page-header {
    text-align: center;
    margin-bottom: var(--spacing-8);
}

.page-title {
    font-size: var(--font-size-3xl);
    color: var(--gray-900);
    margin-bottom: var(--spacing-2);
    font-weight: 700;
}

.page-subtitle {
    color: var(--gray-500);
    font-size: var(--font-size-lg);
}

.section-icon {
    margin-right: var(--spacing-2);
    color: var(--primary-red); /* Usando a cor principal moderna */
}

.contacts-section {
    margin-bottom: var(--spacing-8);
    padding: var(--spacing-6);
    border-radius: var(--border-radius-lg);
    background-color: var(--white);
    box-shadow: var(--shadow-md);
}

.section-title {
    font-size: var(--font-size-2xl);
    color: var(--gray-700);
    border-bottom: 2px solid var(--gray-200);
    padding-bottom: var(--spacing-3);
    margin-bottom: var(--spacing-6);
    font-weight: 600;
}

.contacts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: var(--spacing-6);
}

.contact-card {
    background-color: var(--white);
    border-radius: var(--border-radius-lg);
    padding: var(--spacing-4);
    box-shadow: var(--shadow);
    border: 1px solid var(--gray-100);
    display: flex;
    align-items: center;
    position: relative;
    transition: all var(--transition-normal);
}

.contact-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.contact-icon {
    width: 48px;
    height: 48px;
    border-radius: var(--border-radius); /* Forma mais quadrada, menos circular */
    display: flex;
    justify-content: center;
    align-items: center;
    color: var(--white);
    font-size: var(--font-size-xl);
    margin-right: var(--spacing-4);
    box-shadow: var(--shadow-sm);
}

.contact-info h3 {
    margin: 0 0 var(--spacing-1) 0;
    font-size: var(--font-size-lg);
    color: var(--gray-900);
    font-weight: 600;
}

.contact-value {
    margin: 0;
    color: var(--gray-500);
    word-break: break-word;
    font-size: var(--font-size-base);
}

.contact-value a {
    color: var(--primary-red);
    text-decoration: none;
    transition: color var(--transition-fast);
}

.contact-value a:hover {
    color: var(--primary-red-hover);
    text-decoration: underline;
}

/* Estilos para Redes Sociais */
.social-grid {
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
}

.social-card {
    display: block;
    text-align: center;
    padding: var(--spacing-4);
    background-color: var(--gray-50);
    border-radius: var(--border-radius-lg);
    transition: all var(--transition-normal);
}

.social-card:hover {
    background-color: var(--gray-100);
}

.social-card .contact-icon {
    margin: 0 auto var(--spacing-3) auto;
    width: 60px;
    height: 60px;
    font-size: 2em;
    border-radius: 50%;
}

.social-link {
    display: inline-block;
    margin-top: var(--spacing-2);
    font-weight: 500;
    color: var(--gray-700);
}

.social-link i {
    margin-left: var(--spacing-1);
    font-size: 0.9em;
}

/* Estado Vazio */
.empty-state {
    text-align: center;
    padding: var(--spacing-12);
    border: 2px dashed var(--gray-300);
    border-radius: var(--border-radius-lg);
    color: var(--gray-500);
    background-color: var(--gray-50);
}

.empty-state i {
    font-size: 3.5em;
    margin-bottom: var(--spacing-4);
    color: var(--gray-300);
}

.empty-state h3 {
    color: var(--gray-700);
    margin-bottom: var(--spacing-2);
}

/* Área Administrativa (Coordenador) */
.admin-section {
    margin-bottom: var(--spacing-8);
}

.admin-card {
    background-color: var(--primary-red-light);
    border-left: 5px solid var(--primary-red);
    padding: var(--spacing-4);
    border-radius: var(--border-radius-lg);
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: var(--shadow);
}

.admin-header {
    display: flex;
    align-items: center;
}

.admin-header i {
    font-size: 2.5em;
    color: var(--primary-red);
    margin-right: var(--spacing-4);
}

.admin-header h3 {
    margin: 0 0 var(--spacing-1) 0;
    color: var(--gray-900);
    font-weight: 600;
}

.admin-header p {
    margin: 0;
    color: var(--gray-500);
    font-size: var(--font-size-sm);
}

.admin-actions .btn {
    margin-left: var(--spacing-3);
}

/* Botões de Ação */
.btn {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
    padding: var(--spacing-3) var(--spacing-6);
    border: 1px solid transparent;
    border-radius: var(--border-radius);
    cursor: pointer;
    font-weight: 500;
    transition: all var(--transition-fast);
    text-decoration: none;
    white-space: nowrap;
}

.btn-primary {
    background-color: var(--primary-red);
    color: var(--white);
}

.btn-primary:hover {
    background-color: var(--primary-red-hover);
    color: var(--white);
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

.btn-secondary {
    background-color: var(--white);
    color: var(--gray-700);
    border-color: var(--gray-300);
}

.btn-secondary:hover {
    background-color: var(--gray-50);
    border-color: var(--gray-400);
    color: var(--gray-900);
}

.btn-danger {
    background-color: var(--primary-red);
    color: var(--white);
}

.btn-danger:hover {
    background-color: var(--primary-red-hover);
    color: var(--white);
}

/* Controles de Edição nos Cards */
.edit-controls {
    position: absolute;
    top: var(--spacing-2);
    right: var(--spacing-2);
    display: none; /* Escondido por padrão, mostrado apenas no modo edição */
    gap: var(--spacing-2);
    background: var(--white);
    padding: var(--spacing-1);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    border: 1px solid var(--gray-200);
}

.btn-edit, .btn-delete {
    background: none;
    border: none;
    cursor: pointer;
    font-size: var(--font-size-base);
    padding: var(--spacing-2);
    border-radius: var(--border-radius);
    transition: background-color var(--transition-fast);
}

.btn-edit {
    color: var(--warning);
}

.btn-edit:hover {
    background-color: rgba(255, 193, 7, 0.1);
}

.btn-delete {
    color: var(--primary-red);
}

.btn-delete:hover {
    background-color: var(--primary-red-light);
}

/* Modal */
.modal {
    display: none; /* Escondido por padrão */
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.6);
    justify-content: center;
    align-items: center;
    backdrop-filter: blur(5px);
}

.modal-content {
    background-color: var(--white);
    margin: auto;
    padding: var(--spacing-6);
    border-radius: var(--border-radius-lg);
    width: 90%;
    max-width: 500px;
    box-shadow: var(--shadow-xl);
    position: relative;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--gray-200);
    padding-bottom: var(--spacing-3);
    margin-bottom: var(--spacing-4);
}

.modal-header h2 {
    margin: 0;
    font-size: var(--font-size-xl);
    color: var(--gray-900);
    font-weight: 600;
}

.modal-close {
    color: var(--gray-500);
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
    background: none;
    border: none;
    transition: color var(--transition-fast);
}

.modal-close:hover,
.modal-close:focus {
    color: var(--gray-900);
    text-decoration: none;
    cursor: pointer;
}

.form-group {
    margin-bottom: var(--spacing-4);
}

.form-group label {
    display: block;
    margin-bottom: var(--spacing-2);
    font-weight: 500;
    color: var(--gray-700);
    font-size: var(--font-size-base);
}

.form-group input[type="text"],
.form-group input[type="number"],
.form-group input[type="color"],
.form-group select {
    width: 100%;
    padding: var(--spacing-3);
    border: 1px solid var(--gray-300);
    border-radius: var(--border-radius);
    box-sizing: border-box;
    transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: var(--primary-red);
    box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
}

.form-group small {
    display: block;
    margin-top: var(--spacing-1);
    color: var(--gray-500);
    font-size: var(--font-size-sm);
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: var(--spacing-3);
    margin-top: var(--spacing-6);
}

.modal-small {
    max-width: 400px;
}
</style>

</style>

<div class="main-content">
    <div class="suporte-container">
        <!-- Cabeçalho da página -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-headset section-icon"></i>
                Central de Suporte
            </h1>
            <p class="page-subtitle">Encontre todas as informações de contato da nossa instituição</p>
        </div>

        <?php if (isset($error_message)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
        <?php endif; ?>

        <!-- Área exclusiva do coordenador -->
        <?php if ($user_type === 'coordenador'): ?>
        <div class="admin-section">
            <div class="admin-card">
                <div class="admin-header">
                    <i class="fas fa-user-shield"></i>
                    <div>
                        <h3>Área Administrativa</h3>
                        <p>Gerencie as informações de contato exibidas para usuários</p>
                    </div>
                </div>
                <div class="admin-actions">
                    <button class="btn btn-primary" onclick="openCreateModal()">
                        <i class="fas fa-plus"></i>
                        Adicionar Contato
                    </button>
                    <button class="btn btn-secondary" onclick="toggleEditMode()">
                        <i class="fas fa-edit"></i>
                        <span id="editModeText">Modo Edição</span>
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Seção de contatos institucionais -->
        <?php if (!empty($contatos_organizados['institucionais'])): ?>
        <div class="contacts-section">
            <h2 class="section-title">
                <i class="fas fa-building"></i>
                Informações Institucionais
            </h2>
            <div class="contacts-grid">
                <?php foreach ($contatos_organizados['institucionais'] as $contato): ?>
                <div class="contact-card" data-id="<?php echo $contato['id']; ?>">
                    <?php if ($user_type === 'coordenador'): ?>
                    <div class="edit-controls" style="display: none;">
                        <button class="btn-edit" onclick="editContato(<?php echo $contato['id']; ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-delete" onclick="deleteContato(<?php echo $contato['id']; ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="contact-icon" style="background-color: <?php echo $contato['cor']; ?>">
                        <i class="<?php echo $contato['icone']; ?>"></i>
                    </div>
                    <div class="contact-info">
                        <h3><?php echo htmlspecialchars($contato['titulo']); ?></h3>
                        <p class="contact-value">
                            <?php if (filter_var($contato['valor'], FILTER_VALIDATE_EMAIL)): ?>
                                <a href="mailto:<?php echo $contato['valor']; ?>"><?php echo htmlspecialchars($contato['valor']); ?></a>
                            <?php elseif (strpos($contato['valor'], 'http') === 0): ?>
                                <a href="<?php echo $contato['valor']; ?>" target="_blank"><?php echo htmlspecialchars($contato['valor']); ?></a>
                            <?php else: ?>
                                <?php echo htmlspecialchars($contato['valor']); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Seção de contatos de professores -->
        <?php if (!empty($contatos_organizados['professores'])): ?>
        <div class="contacts-section">
            <h2 class="section-title">
                <i class="fas fa-chalkboard-teacher"></i>
                Contatos dos Professores
            </h2>
            <div class="contacts-grid">
                <?php foreach ($contatos_organizados['professores'] as $contato): ?>
                <div class="contact-card" data-id="<?php echo $contato['id']; ?>">
                    <?php if ($user_type === 'coordenador'): ?>
                    <div class="edit-controls" style="display: none;">
                        <button class="btn-edit" onclick="editContato(<?php echo $contato['id']; ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-delete" onclick="deleteContato(<?php echo $contato['id']; ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="contact-icon" style="background-color: <?php echo $contato['cor']; ?>">
                        <i class="<?php echo $contato['icone']; ?>"></i>
                    </div>
                    <div class="contact-info">
                        <h3><?php echo htmlspecialchars($contato['titulo']); ?></h3>
                        <p class="contact-value">
                            <?php if (filter_var($contato['valor'], FILTER_VALIDATE_EMAIL)): ?>
                                <a href="mailto:<?php echo $contato['valor']; ?>"><?php echo htmlspecialchars($contato['valor']); ?></a>
                            <?php else: ?>
                                <?php echo htmlspecialchars($contato['valor']); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Seção de redes sociais -->
        <?php if (!empty($contatos_organizados['sociais'])): ?>
        <div class="contacts-section">
            <h2 class="section-title">
                <i class="fas fa-share-alt"></i>
                Redes Sociais
            </h2>
            <div class="contacts-grid social-grid">
                <?php foreach ($contatos_organizados['sociais'] as $contato): ?>
                <div class="contact-card social-card" data-id="<?php echo $contato['id']; ?>">
                    <?php if ($user_type === 'coordenador'): ?>
                    <div class="edit-controls" style="display: none;">
                        <button class="btn-edit" onclick="editContato(<?php echo $contato['id']; ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-delete" onclick="deleteContato(<?php echo $contato['id']; ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="contact-icon" style="background-color: <?php echo $contato['cor']; ?>">
                        <i class="<?php echo $contato['icone']; ?>"></i>
                    </div>
                    <div class="contact-info">
                        <h3><?php echo htmlspecialchars($contato['titulo']); ?></h3>
                        <a href="<?php echo $contato['valor']; ?>" target="_blank" class="social-link">
                            Visitar página
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Estado vazio -->
        <?php if (empty($contatos)): ?>
        <div class="empty-state">
            <i class="fas fa-address-book"></i>
            <h3>Nenhum contato disponível</h3>
            <p>
                <?php if ($user_type === 'coordenador'): ?>
                Clique em "Adicionar Contato" para criar o primeiro contato.
                <?php else: ?>
                As informações de contato ainda não foram configuradas.
                <?php endif; ?>
            </p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($user_type === 'coordenador'): ?>
<!-- Modal para criar/editar contato -->
<div id="contatoModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Novo Contato</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        
        <form id="contatoForm">
            <input type="hidden" name="ajax" value="1">
            <input type="hidden" id="formAction" name="action" value="create">
            <input type="hidden" id="contatoId" name="id" value="">
            
            <div class="form-group">
                <label for="tipo">Tipo/Identificador *</label>
	            <input type="text" id="tipo" name="tipo" required 
	                       placeholder="ex: prof_fisica, telefone_secretaria">
	                <small>Identificador único (sem espaços, use underscore)</small>
	            </div>
	            
	            <div class="form-group">
	                <label for="secao_exibicao">Seção de Exibição *</label>
	                <select id="secao_exibicao" name="secao_exibicao" required>
	                    <option value="institucionais">Informações Institucionais</option>
	                    <option value="professores">Contatos dos Professores</option>
	                    <option value="sociais">Redes Sociais</option>
	                </select>
            </div>
            
            <div class="form-group">
                <label for="titulo">Título *</label>
                <input type="text" id="titulo" name="titulo" required 
                       placeholder="ex: Prof. Física, Telefone da Secretaria">
            </div>
            
            <div class="form-group">
                <label for="valor">Valor do Contato *</label>
                <input type="text" id="valor" name="valor" required 
                       placeholder="email, telefone, endereço ou URL">
            </div>
            
            <div class="form-group">
                <label for="icone">Ícone (Font Awesome) *</label>
                <select id="icone" name="icone" required>
                    <option value="fas fa-envelope">📧 Email (fas fa-envelope)</option>
                    <option value="fas fa-phone">📞 Telefone (fas fa-phone)</option>
                    <option value="fas fa-map-marker-alt">📍 Endereço (fas fa-map-marker-alt)</option>
                    <option value="fas fa-user-tie">👔 Coordenação (fas fa-user-tie)</option>
                    <option value="fas fa-chalkboard-teacher">👨‍🏫 Professor (fas fa-chalkboard-teacher)</option>
                    <option value="fas fa-calculator">🔢 Matemática (fas fa-calculator)</option>
                    <option value="fas fa-book-open">📖 Português (fas fa-book-open)</option>
                    <option value="fas fa-flask">🧪 Química (fas fa-flask)</option>
                    <option value="fas fa-atom">⚛️ Física (fas fa-atom)</option>
                    <option value="fas fa-landmark">🏛️ História (fas fa-landmark)</option>
                    <option value="fas fa-globe">🌍 Geografia (fas fa-globe)</option>
                    <option value="fab fa-instagram">📷 Instagram (fab fa-instagram)</option>
                    <option value="fab fa-facebook">📘 Facebook (fab fa-facebook)</option>
                    <option value="fab fa-youtube">📺 YouTube (fab fa-youtube)</option>
                    <option value="fab fa-twitter">🐦 Twitter (fab fa-twitter)</option>
                    <option value="fab fa-whatsapp">💬 WhatsApp (fab fa-whatsapp)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="cor">Cor do Ícone</label>
                <input type="color" id="cor" name="cor" value="#d32f2f">
            </div>
            
            <div class="form-group">
                <label for="ordem">Ordem de Exibição</label>
                <input type="number" id="ordem" name="ordem" min="0" value="100">
                <small>Menor número aparece primeiro</small>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de confirmação para deletar -->
<div id="deleteModal" class="modal">
    <div class="modal-content modal-small">
        <div class="modal-header">
            <h2>Confirmar Exclusão</h2>
            <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        
        <div class="modal-body">
            <p>Tem certeza que deseja deletar este contato?</p>
            <p><strong>Esta ação não pode ser desfeita.</strong></p>
        </div>
        
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancelar</button>
            <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                <i class="fas fa-trash"></i> Deletar
            </button>
        </div>
    </div>
</div>

<script>
let editMode = false;
let currentDeleteId = null;


function toggleEditMode() {
    editMode = !editMode;
    const editControls = document.querySelectorAll('.edit-controls');
    const editModeText = document.getElementById('editModeText');
    
    editControls.forEach(control => {
        control.style.display = editMode ? 'flex' : 'none';
    });
    
    editModeText.textContent = editMode ? 'Sair da Edição' : 'Modo Edição';
}


function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Novo Contato';
    document.getElementById('formAction').value = 'create';
    document.getElementById('contatoForm').reset();
    document.getElementById('contatoId').value = '';
    document.getElementById('secao_exibicao').value = 'institucionais'; 
    document.getElementById('cor').value = '#d32f2f';
    document.getElementById('contatoModal').style.display = 'flex';
}

function editContato(id) {
    
    fetch('suporte_ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `ajax=1&action=get_contato&id=${id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const contato = data.contato;
            document.getElementById('modalTitle').textContent = 'Editar Contato';
            document.getElementById('formAction').value = 'update';
            document.getElementById('contatoId').value = contato.id;
            document.getElementById('tipo').value = contato.tipo;
            document.getElementById('titulo').value = contato.titulo;
            document.getElementById('valor').value = contato.valor;
            document.getElementById('icone').value = contato.icone;
            document.getElementById('cor').value = contato.cor;
            document.getElementById('ordem').value = contato.ordem;
            
           
            let secao = contato.secao_exibicao || 'institucionais';
            if (!contato.secao_exibicao) {
                if (contato.tipo.startsWith('prof_') || contato.tipo === 'coordenacao') {
                    secao = 'professores';
                } else if (contato.tipo.endsWith('_social')) {
                    secao = 'sociais';
                }
            }
            document.getElementById('secao_exibicao').value = secao;
            document.getElementById('contatoModal').style.display = 'flex';
        }
    });
}

function deleteContato(id) {
    currentDeleteId = id;
    document.getElementById('deleteModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('contatoModal').style.display = 'none';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
    currentDeleteId = null;
}

function confirmDelete() {
    if (currentDeleteId) {
        fetch('suporte_ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `ajax=1&action=delete&id=${currentDeleteId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('success', data.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                showMessage('error', data.message);
            }
            closeDeleteModal();
        });
    }
}


document.getElementById('contatoForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
      formData.append('secao_exibicao', document.getElementById('secao_exibicao').value);

    fetch('suporte_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage('success', data.message);
            closeModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showMessage('error', data.message);
        }
    });
});


function showMessage(type, message) {
    console.log(`[${type.toUpperCase()}] ${message}`);
    }
</script>

<?php endif; ?>