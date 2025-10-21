<?php
/**
 * Página de Chamada - Professor
 * Fluxus Project - Optimized Version com design TCC 2.0
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: index.php");
    exit();
}

require_once "../includes/connection.php"; // Adjusted path

$professor_id = $_SESSION["user_id"] ?? null;

if (!$professor_id) {
    // If no professor ID is set, redirect to login page for security.
    header("Location: ../login.php");
    exit();
}

// Get professor's disciplines
$disciplinas = [];
if (isset($con) && $con) {
    try {
        $sql_disciplinas = "SELECT id, nome FROM disciplina ORDER BY nome";
        $stmt_disciplinas = $con->prepare($sql_disciplinas);
        if ($stmt_disciplinas) {
            $stmt_disciplinas->execute();
            $disciplinas = $stmt_disciplinas->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error_message .= "Erro ao preparar a consulta de disciplinas.<br>";
        }
    } catch (PDOException $e) {
        $error_message .= "Erro ao buscar disciplinas: " . $e->getMessage() . "<br>";
    }
} else {
    $error_message .= "Erro: Conexão com o banco de dados não estabelecida ou inválida.<br>";
}

$success_message = "";
$error_message = "";

// Handle form submission for attendance
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "register_attendance") {
    $disciplina_id_post = $_POST["disciplina_id"];
    $data = $_POST["data"];
    $conteudo = $_POST["conteudo"];
    $alunos_presentes = isset($_POST["presentes"]) ? $_POST["presentes"] : [];

    // Placeholder for turma_id. This needs to be addressed in your DB schema.
    $dummy_turma_id = 1; 

    $con->beginTransaction();
    try {
        // 1. Insert new aula record
        $sql_insert_aula = "INSERT INTO aula (turma_id, data, conteudo, criado_por) VALUES (?, ?, ?, ?)";
        $stmt_insert_aula = $con->prepare($sql_insert_aula);
        $stmt_insert_aula->execute([$dummy_turma_id, $data, $conteudo, $professor_id]);
        $aula_id = $con->lastInsertId();

        // 2. Get all students for the selected discipline
        $alunos_da_disciplina = [];
        $sql_alunos_disciplina_para_aula = "SELECT u.id FROM usuario u 
                                            JOIN matricula m ON u.id = m.aluno_id 
                                            WHERE m.disciplina_id = ?";
        $stmt_alunos_disciplina_para_aula = $con->prepare($sql_alunos_disciplina_para_aula);
        $stmt_alunos_disciplina_para_aula->execute([$disciplina_id_post]);
        $alunos_da_disciplina_raw = $stmt_alunos_disciplina_para_aula->fetchAll(PDO::FETCH_ASSOC);
        foreach ($alunos_da_disciplina_raw as $aluno_row) {
            $alunos_da_disciplina[] = $aluno_row["id"];
        }

        // 3. Insert attendance records
        foreach ($alunos_da_disciplina as $aluno_id) {
            $presente = in_array($aluno_id, $alunos_presentes) ? 1 : 0;
            $sql_insert_frequencia = "INSERT INTO frequencia (aula_id, aluno_id, presente) VALUES (?, ?, ?)";
            $stmt_insert_frequencia = $con->prepare($sql_insert_frequencia);
            $stmt_insert_frequencia->execute([$aula_id, $aluno_id, $presente]);
        }
        
        $con->commit();
        $success_message = "Chamada registrada com sucesso para a disciplina!";
    } catch (Exception $e) {
        $con->rollBack();
        $error_message = "Erro ao registrar chamada: " . $e->getMessage();
    }
}

// Get selected discipline students if disciplina_id is provided
$selected_disciplina_id = $_GET["disciplina_id"] ?? null;
$alunos_disciplina = [];
if ($selected_disciplina_id) {
    // Get all students for the selected discipline
    $sql_alunos_disciplina = "SELECT DISTINCT u.id, u.nome FROM usuario u 
                                WHERE u.tipo = 'aluno' 
                                ORDER BY u.nome";
    $stmt_alunos_disciplina = $con->prepare($sql_alunos_disciplina);
    $stmt_alunos_disciplina->execute();
    $alunos_disciplina = $stmt_alunos_disciplina->fetchAll(PDO::FETCH_ASSOC);
}

// // A conexão será fechada automaticamente no final do script ou pode ser gerenciada globalmente.
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chamada</title>
    <link rel="stylesheet" href="../css/modern.css" />
    <link rel="stylesheet" href="../css/chamada.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php require_once "../includes/header.php"; ?>

    <main class="container-principal">
        <div class="panel-header">
            <div class="icon"><i class="fas fa-clipboard-list"></i></div>
            <h2>Chamada</h2>
        </div>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="alert error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div id="professorView" class="view-section active">
                <div class="step-card panel">
                    <div class="panel-header-small">
                        <i class="fas fa-book"></i> <h3>1. Selecione a Disciplina</h3>
                    </div>
                    <form method="GET" action="chamada.php">
                        <select name="disciplina_id" onchange="this.form.submit()" required>
                            <option value="">Selecione uma disciplina...</option>
                            <?php foreach ($disciplinas as $disciplina): ?>
                                <option value="<?php echo $disciplina["id"]; ?>" <?php echo $selected_disciplina_id == $disciplina["id"] ? "selected" : ""; ?>>
                                    <?php echo htmlspecialchars($disciplina["nome"]); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>
            
            <?php if ($selected_disciplina_id && !empty($alunos_disciplina)): ?>
            <div class="chamada-alunos-section">
                <div class="step-card panel">
                    <div class="panel-header-small">
                        <i class="fas fa-clipboard-check"></i> <h3>2. Registrar Aula e Presença</h3>
                    </div>
                    <form method="POST" action="chamada.php">
                        <input type="hidden" name="action" value="register_attendance">
                        <input type="hidden" name="disciplina_id" value="<?php echo $selected_disciplina_id; ?>">
                        
                        <div class="aula-info">
                            <div class="form-group">
                                <label for="data">Data da Aula:</label>
                                <input type="date" name="data" id="data" value="<?php echo date("Y-m-d"); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="conteudo">Conteúdo da Aula:</label>
                                <textarea name="conteudo" id="conteudo" rows="3" placeholder="Descreva o conteúdo abordado na aula..."></textarea>
                            </div>
                        </div>
                        
                        <div class="lista-alunos">
                            <h3>Lista de Presença</h3>
                            <div class="alunos-table-container">
                                <table class="alunos-table">
                                    <thead>
                                        <tr>
                                            <th>Nome do Aluno</th>
                                            <th>Presente</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($alunos_disciplina as $aluno): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($aluno["nome"]); ?></td>
                                            <td>
                                                <label class="checkbox-container">
                                                    <input type="checkbox" name="presentes[]" value="<?php echo $aluno["id"]; ?>" checked>
                                                    <span class="checkmark"></span>
                                                </label>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Registrar Chamada
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="marcarTodos()">
                                <i class="fas fa-check-double"></i> Marcar Todos
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="desmarcarTodos()">
                                <i class="fas fa-times"></i> Desmarcar Todos
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php elseif ($selected_disciplina_id): ?>
            <div class="chamada-alunos-section">
                <div class="no-students panel">
                    <i class="fas fa-user-slash"></i>
                    <p>Nenhum aluno encontrado para esta disciplina.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function marcarTodos() {
            const checkboxes = document.querySelectorAll("input[type=\"checkbox\"][name^=\"presentes\"]");
            checkboxes.forEach(checkbox => checkbox.checked = true);
        }

        function desmarcarTodos() {
            const checkboxes = document.querySelectorAll("input[type=\"checkbox\"][name^=\"presentes\"]");
            checkboxes.forEach(checkbox => checkbox.checked = false);
        }
    </script>
</body>
</html>

