/**
 * Scripts JavaScript do Marketplace Digital
 * Funcionalidades interativas e melhorias de UX
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips do Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Inicializar popovers do Bootstrap
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Auto-hide alerts após 5 segundos
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Confirmação para ações de exclusão
    const deleteButtons = document.querySelectorAll('.btn-delete, .delete-btn');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Tem certeza que deseja excluir este item?')) {
                e.preventDefault();
                return false;
            }
        });
    });

    // Atualização automática de quantidade no carrinho
    const quantityInputs = document.querySelectorAll('input[name="quantity"]');
    quantityInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            const form = this.closest('form');
            if (form && this.value >= 0) {
                // Auto-submit form on quantity change
                form.submit();
            }
        });
    });

    // Formatação de valores monetários em tempo real
    const priceInputs = document.querySelectorAll('input[type="number"][step="0.01"]');
    priceInputs.forEach(function(input) {
        input.addEventListener('blur', function() {
            const value = parseFloat(this.value);
            if (!isNaN(value)) {
                this.value = value.toFixed(2);
            }
        });
    });

    // Loading state para botões de submit
    const forms = document.querySelectorAll('form');
    forms.forEach(function(form) {
        form.addEventListener('submit', function() {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Processando...';
                
                // Restaurar botão após 3 segundos se não houver redirect
                setTimeout(function() {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }, 3000);
            }
        });
    });

    // Smooth scrolling para links âncora
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    anchorLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // Destacar campo com erro
    const invalidInputs = document.querySelectorAll('.is-invalid');
    invalidInputs.forEach(function(input) {
        input.addEventListener('input', function() {
            this.classList.remove('is-invalid');
        });
    });

    // Contador de caracteres para textareas
    const textareas = document.querySelectorAll('textarea[maxlength]');
    textareas.forEach(function(textarea) {
        const maxLength = textarea.getAttribute('maxlength');
        const counter = document.createElement('small');
        counter.className = 'form-text text-muted text-end d-block';
        counter.innerHTML = `0/${maxLength} caracteres`;
        textarea.parentNode.appendChild(counter);

        textarea.addEventListener('input', function() {
            const currentLength = this.value.length;
            counter.innerHTML = `${currentLength}/${maxLength} caracteres`;
            
            if (currentLength > maxLength * 0.9) {
                counter.className = 'form-text text-warning text-end d-block';
            } else {
                counter.className = 'form-text text-muted text-end d-block';
            }
        });
    });

    // Validação de formulário em tempo real
    const emailInputs = document.querySelectorAll('input[type="email"]');
    emailInputs.forEach(function(input) {
        input.addEventListener('blur', function() {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (this.value && !emailRegex.test(this.value)) {
                this.classList.add('is-invalid');
                showValidationError(this, 'Por favor, insira um email válido');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    });

    // Função para mostrar erros de validação
    function showValidationError(input, message) {
        let feedback = input.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            input.parentNode.appendChild(feedback);
        }
        feedback.textContent = message;
    }

    // Preview de imagem para upload
    const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    imageInputs.forEach(function(input) {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    let preview = document.querySelector('#image-preview');
                    if (!preview) {
                        preview = document.createElement('img');
                        preview.id = 'image-preview';
                        preview.className = 'img-thumbnail mt-2';
                        preview.style.maxWidth = '200px';
                        input.parentNode.appendChild(preview);
                    }
                    preview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    });

    // Busca em tempo real (se houver campo de busca)
    const searchInput = document.querySelector('#search-input');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length >= 2) {
                searchTimeout = setTimeout(function() {
                    performSearch(query);
                }, 300);
            }
        });
    }

    function performSearch(query) {
        // Implementar busca AJAX aqui se necessário
        console.log('Searching for:', query);
    }

    // Animações de entrada para cards
    const cards = document.querySelectorAll('.card');
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in-up');
            }
        });
    }, {
        threshold: 0.1
    });

    cards.forEach(function(card) {
        observer.observe(card);
    });

    // Confirmar antes de sair da página com formulário não salvo
    let formChanged = false;
    const trackableForms = document.querySelectorAll('form:not(.no-track)');
    
    trackableForms.forEach(function(form) {
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(function(input) {
            input.addEventListener('change', function() {
                formChanged = true;
            });
        });

        form.addEventListener('submit', function() {
            formChanged = false;
        });
    });

    window.addEventListener('beforeunload', function(e) {
        if (formChanged) {
            e.preventDefault();
            e.returnValue = 'Você tem alterações não salvas. Deseja realmente sair?';
        }
    });

    // Copiar texto para clipboard
    const copyButtons = document.querySelectorAll('.copy-btn');
    copyButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const target = document.querySelector(this.getAttribute('data-target'));
            if (target) {
                const text = target.textContent || target.value;
                navigator.clipboard.writeText(text).then(function() {
                    showToast('Texto copiado!', 'success');
                });
            }
        });
    });

    // Sistema de toast notifications
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            document.body.appendChild(toastContainer);
        }

        toastContainer.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();

        toast.addEventListener('hidden.bs.toast', function() {
            toast.remove();
        });
    }

    // Lazy loading para imagens
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                imageObserver.unobserve(img);
            }
        });
    });

    images.forEach(function(img) {
        imageObserver.observe(img);
    });
});

// Utility functions
function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
}

function formatDate(date) {
    return new Intl.DateTimeFormat('pt-BR').format(new Date(date));
}

// Exportar funções para uso global
window.MarketplaceUtils = {
    formatCurrency,
    formatDate,
    showToast: function(message, type = 'info') {
        // Implementar toast aqui se necessário
        console.log(`Toast: ${message} (${type})`);
    }
};