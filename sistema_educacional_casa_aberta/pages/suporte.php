<?php
/**
 * Página de Suporte - Sistema Educacional
 * Exibe informações de contato e permite gerenciamento pelo coordenador
 */

session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/connection.php';

$user_type = $_SESSION['user_type'] ?? 'aluno';
$username = $_SESSION['username'] ?? 'Usuário';

// Buscar contatos de suporte
try {
    $stmt = $pdo->prepare("
        SELECT id, tipo, titulo, valor, icone, cor, ordem 
        FROM contato_suporte 
        WHERE ativo = 1 
        ORDER BY ordem ASC, titulo ASC
    ");
    $stmt->execute();
    $contatos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organizar contatos por categoria
    $contatos_organizados = [
        'institucionais' => [],
        'professores' => [],
        'sociais' => []
    ];
    
    foreach ($contatos as $contato) {
        if (strpos($contato['tipo'], 'prof_') === 0 || $contato['tipo'] === 'coordenacao') {
            $contatos_organizados['professores'][] = $contato;
        } elseif (strpos($contato['tipo'], '_social') !== false) {
            $contatos_organizados['sociais'][] = $contato;
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

<?php include '../includes/header.php'; ?>

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
            <input type="hidden" id="contatoId" name="id">
            <input type="hidden" name="ajax" value="1">
            <input type="hidden" id="formAction" name="action" value="create">
            
            <div class="form-group">
                <label for="tipo">Tipo/Identificador *</label>
                <input type="text" id="tipo" name="tipo" required 
                       placeholder="ex: prof_fisica, telefone_secretaria">
                <small>Identificador único (sem espaços, use underscore)</small>
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

// Toggle modo de edição
function toggleEditMode() {
    editMode = !editMode;
    const editControls = document.querySelectorAll('.edit-controls');
    const editModeText = document.getElementById('editModeText');
    
    editControls.forEach(control => {
        control.style.display = editMode ? 'flex' : 'none';
    });
    
    editModeText.textContent = editMode ? 'Sair da Edição' : 'Modo Edição';
}

// Modal functions
function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Novo Contato';
    document.getElementById('formAction').value = 'create';
    document.getElementById('contatoForm').reset();
    document.getElementById('contatoId').value = '';
    document.getElementById('cor').value = '#d32f2f';
    document.getElementById('contatoModal').style.display = 'flex';
}

function editContato(id) {
    // Buscar dados do contato
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

// Form submission
document.getElementById('contatoForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
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
    })
    .catch(error => {
        showMessage('error', 'Erro de conexão');
    });
});

// Message system
function showMessage(type, message) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `message message-${type}`;
    messageDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        ${message}
    `;
    
    document.body.appendChild(messageDiv);
    
    setTimeout(() => {
        messageDiv.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        messageDiv.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(messageDiv);
        }, 300);
    }, 3000);
}

// Close modal when clicking outside
window.addEventListener('click', function(e) {
    const contatoModal = document.getElementById('contatoModal');
    const deleteModal = document.getElementById('deleteModal');
    
    if (e.target === contatoModal) {
        closeModal();
    }
    if (e.target === deleteModal) {
        closeDeleteModal();
    }
});
</script>
<?php endif; ?>

<style>
/* Estilos específicos para a página de suporte */
.suporte-container {
    padding: 2rem;
    max-width: 1400px;
    margin: 0 auto;
}

.page-header {
    margin-bottom: 2rem;
    text-align: center;
}

.page-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
}

.section-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    background: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
}

.page-subtitle {
    color: var(--text-secondary);
    font-size: 1.25rem;
}

.admin-section {
    margin-bottom: 3rem;
}

.admin-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 2px solid var(--primary-color);
    border-radius: 16px;
    padding: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.admin-header {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.admin-header i {
    font-size: 2rem;
    color: var(--primary-color);
}

.admin-header h3 {
    margin: 0;
    color: var(--text-primary);
    font-size: 1.25rem;
}

.admin-header p {
    margin: 0;
    color: var(--text-secondary);
}

.admin-actions {
    display: flex;
    gap: 1rem;
}

.contacts-section {
    margin-bottom: 3rem;
    padding-bottom: 2rem; /* Adiciona espaço abaixo da seção */
    border-bottom: 2px solid var(--gray-900); /* Linha separadora preta */
}

.contacts-section:last-of-type {
    border-bottom: none; /* Remove a linha da última seção */
}

.section-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid var(--border-color);
}

.section-title i {
    color: var(--primary-color);
}

.contacts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.social-grid {
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
}

.contact-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.contact-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.contact-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: var(--primary-color);
}

.social-card::before {
    background: linear-gradient(45deg, #e91e63, #3f51b5);
}

.edit-controls {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    display: flex;
    gap: 0.25rem;
}

.btn-edit,
.btn-delete {
    width: 28px;
    height: 28px;
    border: none;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 0.75rem;
    transition: all 0.2s ease;
}

.btn-edit {
    background: #e3f2fd;
    color: #1976d2;
}

.btn-edit:hover {
    background: #bbdefb;
}

.btn-delete {
    background: #ffebee;
    color: #c62828;
}

.btn-delete:hover {
    background: #ffcdd2;
}

.contact-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.contact-info {
    flex: 1;
}

.contact-info h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--text-primary);
}

.contact-value {
    margin: 0;
    color: var(--text-secondary);
    word-break: break-all;
}

.contact-value a {
    color: var(--primary-color);
    text-decoration: none;
    transition: color 0.2s ease;
}

.contact-value a:hover {
    color: #c62828;
    text-decoration: underline;
}

.social-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s ease;
}

.social-link:hover {
    color: #c62828;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--text-secondary);
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state h3 {
    margin-bottom: 0.5rem;
    color: var(--text-primary);
}

.alert {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 500;
}

.alert-error {
    background: #ffebee;
    color: #c62828;
    border: 1px solid #ffcdd2;
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7); /* Fundo mais escuro para o modal */
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: var(--white); /* Garante fundo branco para o conteúdo do modal */
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: var(--shadow-xl); /* Adiciona sombra para destaque */
}

.modal-small {
    max-width: 400px;
}

.modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
    color: var(--text-primary);
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--text-secondary);
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-body {
    padding: 1.5rem;
}

.modal-actions {
    padding: 1.5rem;
    border-top: 1px solid var(--border-color);
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

.form-group {
    margin-bottom: 1.5rem;
    padding: 0 1.5rem;
}

.form-group:first-of-type {
    padding-top: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--text-primary);
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--gray-300); /* Borda cinza clara */
    border-radius: 8px;
    background: var(--white); /* Fundo branco para campos de input */
    color: var(--gray-900); /* Texto preto */
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary-red); /* Borda vermelha ao focar */
    box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.2); /* Sombra ao focar */
}

.form-group small {
    display: block;
    margin-top: 0.25rem;
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.btn {
    padding: 0.75rem 1rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s ease;
    text-decoration: none;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background: #c62828;
}

.btn-secondary {
    background: var(--gray-200);
    color: var(--text-primary);
}

.btn-secondary:hover {
    background: var(--gray-300);
}

.btn-danger {
    background: #f44336;
    color: white;
}

.btn-danger:hover {
    background: #d32f2f;
}

.message {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    color: white;
    font-weight: 600;
    z-index: 1001;
    transform: translateX(100%);
    transition: transform 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.message.show {
    transform: translateX(0);
}

.message-success {
    background: #4caf50;
}

.message-error {
    background: #f44336;
}

@media (max-width: 768px) {
    .suporte-container {
        padding: 1rem;
    }
    
    .page-title {
        font-size: 2rem;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .admin-card {
        flex-direction: column;
        text-align: center;
    }
    
    .admin-actions {
        width: 100%;
        justify-content: center;
    }
    
    .contacts-grid {
        grid-template-columns: 1fr;
    }
    
    .contact-card {
        flex-direction: column;
        text-align: center;
    }
    
    .contact-icon {
        width: 80px;
        height: 80px;
        font-size: 2rem;
    }
}
</style>

</body>
</html>
