// Editor.js - Funcionalidades del editor de prompts

document.addEventListener('DOMContentLoaded', function() {
    const promptText = document.getElementById('prompt-text');
    const previewTitle = document.getElementById('preview-title');
    const previewContent = document.getElementById('preview-content');
    const titleInput = document.querySelector('input[name="titulo"]');

    // Actualizar vista previa del título
    if (titleInput) {
        titleInput.addEventListener('keyup', function() {
            if (previewTitle) {
                previewTitle.textContent = this.value || 'Tu título aquí';
            }
        });
    }

    // Actualizar vista previa del contenido
    if (promptText) {
        promptText.addEventListener('keyup', function() {
            if (previewContent) {
                previewContent.textContent = this.value.substring(0, 200) + 
                    (this.value.length > 200 ? '...' : '');
            }
            updateCount();
        });
    }

    // Auto-resize textarea
    if (promptText) {
        promptText.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 500) + 'px';
        });
    }

    // Validar formulario al enviar
    const form = document.querySelector('.prompt-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validarFormularioPrompt()) {
                e.preventDefault();
            }
        });
    }
});

function updateCount() {
    const ta = document.getElementById('prompt-text');
    const cc = document.getElementById('char-count');
    if (ta && cc) {
        const count = ta.value.length;
        cc.textContent = count + ' caracteres';
        
        // Cambiar color si está muy largo
        if (count > 5000) {
            cc.style.color = 'var(--coral)';
        } else if (count > 3000) {
            cc.style.color = 'var(--amber)';
        } else {
            cc.style.color = 'var(--text3)';
        }
    }
}

function validarFormularioPrompt() {
    const titulo = document.querySelector('input[name="titulo"]');
    const contenido = document.getElementById('prompt-text');
    const categoria = document.querySelector('select[name="categoria"]');
    const modelo = document.querySelector('select[name="modelo"]');

    // Validar título
    if (!titulo || !titulo.value.trim()) {
        mostrarError('El título es requerido');
        titulo.focus();
        return false;
    }

    if (titulo.value.trim().length < 5) {
        mostrarError('El título debe tener al menos 5 caracteres');
        titulo.focus();
        return false;
    }

    if (titulo.value.trim().length > 100) {
        mostrarError('El título no puede exceder 100 caracteres');
        return false;
    }

    // Validar contenido
    if (!contenido || !contenido.value.trim()) {
        mostrarError('El contenido del prompt es requerido');
        contenido.focus();
        return false;
    }

    if (contenido.value.trim().length < 20) {
        mostrarError('El contenido debe tener al menos 20 caracteres');
        contenido.focus();
        return false;
    }

    // Validar categoría
    if (!categoria || !categoria.value) {
        mostrarError('Debes seleccionar una categoría');
        return false;
    }

    // Validar modelo
    if (!modelo || !modelo.value) {
        mostrarError('Debes seleccionar un modelo IA');
        return false;
    }

    return true;
}

function mostrarError(mensaje) {
    // Crear elemento de alerta si no existe
    let alertElement = document.querySelector('.alert-error');
    if (!alertElement) {
        alertElement = document.createElement('div');
        alertElement.className = 'alert alert-error';
        const form = document.querySelector('.prompt-form');
        if (form) {
            form.parentNode.insertBefore(alertElement, form);
        }
    }
    
    alertElement.textContent = mensaje;
    alertElement.style.display = 'block';
    
    // Auto-ocultar después de 4 segundos
    setTimeout(() => {
        alertElement.style.display = 'none';
    }, 4000);
    
    // Scroll a la alerta
    alertElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// Permitir tabulaciones en el textarea
document.addEventListener('keydown', function(e) {
    const textarea = document.getElementById('prompt-text');
    if (e.key === 'Tab' && document.activeElement === textarea) {
        e.preventDefault();
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        textarea.value = textarea.value.substring(0, start) + '\t' + textarea.value.substring(end);
        textarea.selectionStart = textarea.selectionEnd = start + 1;
        updateCount();
    }
});