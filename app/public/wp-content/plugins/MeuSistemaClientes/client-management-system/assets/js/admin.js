jQuery(document).ready(function($) {
    // Kanban functionality
    if ($('.cms-kanban-board').length) {
        initKanban();
    }

    function initKanban() {
        loadTasks();
        initDragAndDrop();
        initTaskModal();
    }

    function loadTasks() {
        $.ajax({
            url: cmsAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_tasks',
                nonce: cmsAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderTasks(response.data);
                }
            }
        });
    }

    function renderTasks(tasks) {
        $('.cms-kanban-tasks').empty();
        
        tasks.forEach(function(task) {
            const taskHtml = `
                <div class="cms-task-card" draggable="true" data-task-id="${task.id}">
                    <h3>${task.title}</h3>
                    <p>${task.description || ''}</p>
                    <small>Cliente: ${task.client_name}</small>
                </div>
            `;
            
            $(`.cms-kanban-tasks[data-status="${task.status}"]`).append(taskHtml);
        });
    }

    function initDragAndDrop() {
        const tasks = document.querySelectorAll('.cms-task-card');
        const columns = document.querySelectorAll('.cms-kanban-tasks');

        tasks.forEach(task => {
            task.addEventListener('dragstart', dragStart);
            task.addEventListener('dragend', dragEnd);
        });

        columns.forEach(column => {
            column.addEventListener('dragover', dragOver);
            column.addEventListener('drop', drop);
        });
    }

    function dragStart(e) {
        e.target.classList.add('dragging');
    }

    function dragEnd(e) {
        e.target.classList.remove('dragging');
    }

    function dragOver(e) {
        e.preventDefault();
        e.target.classList.add('drag-over');
    }

    function drop(e) {
        e.preventDefault();
        const column = e.target.closest('.cms-kanban-tasks');
        column.classList.remove('drag-over');
        
        const task = document.querySelector('.dragging');
        const taskId = task.dataset.taskId;
        const newStatus = column.dataset.status;
        
        updateTaskStatus(taskId, newStatus);
        column.appendChild(task);
    }

    function updateTaskStatus(taskId, status) {
        $.ajax({
            url: cmsAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'update_task_status',
                nonce: cmsAjax.nonce,
                task_id: taskId,
                status: status
            }
        });
    }

    function initTaskModal() {
        // Open modal on add task button click
        $('.add-task-button').click(function() {
            const status = $(this).data('status');
            $('#task-form')[0].reset();
            $('#task-id').val('');
            $('#task-modal').show();
        });

        // Close modal
        $('.cms-modal-close, .cms-modal-cancel').click(function() {
            $('#task-modal').hide();
        });

        // Handle form submission
        $('#task-form').submit(function(e) {
            e.preventDefault();
            
            const formData = {
                action: 'save_task',
                nonce: cmsAjax.nonce,
                task_id: $('#task-id').val(),
                title: $('#task-title').val(),
                description: $('#task-description').val(),
                client_id: $('#task-client').val()
            };

            $.ajax({
                url: cmsAjax.ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        $('#task-modal').hide();
                        loadTasks();
                    }
                }
            });
        });
    }
});
