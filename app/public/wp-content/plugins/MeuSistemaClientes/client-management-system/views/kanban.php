<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>Kanban - Gerenciamento de Tarefas</h1>
    
    <div class="cms-kanban-board">
        <!-- To Do Column -->
        <div class="cms-kanban-column" id="todo-column">
            <h2>A Fazer</h2>
            <div class="cms-kanban-tasks" data-status="todo">
                <!-- Tasks will be loaded here via JavaScript -->
            </div>
            <button class="button add-task-button" data-status="todo">+ Adicionar Tarefa</button>
        </div>
        
        <!-- In Progress Column -->
        <div class="cms-kanban-column" id="doing-column">
            <h2>Em Andamento</h2>
            <div class="cms-kanban-tasks" data-status="doing">
                <!-- Tasks will be loaded here via JavaScript -->
            </div>
            <button class="button add-task-button" data-status="doing">+ Adicionar Tarefa</button>
        </div>
        
        <!-- Done Column -->
        <div class="cms-kanban-column" id="done-column">
            <h2>Concluído</h2>
            <div class="cms-kanban-tasks" data-status="done">
                <!-- Tasks will be loaded here via JavaScript -->
            </div>
            <button class="button add-task-button" data-status="done">+ Adicionar Tarefa</button>
        </div>
    </div>
</div>

<!-- Task Modal -->
<div id="task-modal" class="cms-modal" style="display: none;">
    <div class="cms-modal-content">
        <span class="cms-modal-close">&times;</span>
        <h2>Tarefa</h2>
        <form id="task-form">
            <input type="hidden" id="task-id" name="task_id">
            <div class="form-field">
                <label for="task-title">Título</label>
                <input type="text" id="task-title" name="title" required>
            </div>
            <div class="form-field">
                <label for="task-description">Descrição</label>
                <textarea id="task-description" name="description"></textarea>
            </div>
            <div class="form-field">
                <label for="task-client">Cliente</label>
                <select id="task-client" name="client_id" required>
                    <!-- Clients will be loaded here via JavaScript -->
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" class="button button-primary">Salvar</button>
                <button type="button" class="button cms-modal-cancel">Cancelar</button>
            </div>
        </form>
    </div>
</div>
