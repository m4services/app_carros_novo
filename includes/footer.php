<?php if ($auth->isLoggedIn() && basename($_SERVER['PHP_SELF']) !== 'login.php'): ?>
            </main>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('sw.js')
                    .then(function(registration) {
                        console.log('ServiceWorker registration successful');
                    })
                    .catch(function(err) {
                        console.log('ServiceWorker registration failed: ', err);
                    });
            });
        }
    </script>
    
    <!-- Custom JavaScript -->
    <script>
        // Função para mostrar loading
        function showLoading(element) {
            const loading = element.querySelector('.loading');
            if (loading) {
                loading.classList.add('show');
            }
            element.disabled = true;
            element.style.opacity = '0.7';
        }
        
        // Função para esconder loading
        function hideLoading(element) {
            const loading = element.querySelector('.loading');
            if (loading) {
                loading.classList.remove('show');
            }
            element.disabled = false;
            element.style.opacity = '1';
        }
        
        // Confirmar exclusões
        function confirmDelete(message = 'Tem certeza que deseja excluir este item? Esta ação não pode ser desfeita.') {
            return confirm('⚠️ ATENÇÃO\n\n' + message);
        }
        
        // Preview de imagens
        function previewImage(input, previewElement) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewElement.src = e.target.result;
                    previewElement.style.display = 'block';
                    previewElement.style.animation = 'fadeInUp 0.5s ease';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Função para formatar números
        function formatNumber(num) {
            return new Intl.NumberFormat('pt-BR').format(num);
        }
        
        // Função para formatar moeda
        function formatCurrency(value) {
            return new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            }).format(value);
        }
        
        // Função para mostrar toast notifications
        function showToast(message, type = 'info') {
            const toastContainer = document.getElementById('toast-container') || createToastContainer();
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type} border-0`;
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi bi-${getToastIcon(type)} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            
            toastContainer.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            
            toast.addEventListener('hidden.bs.toast', () => {
                toast.remove();
            });
        }
        
        function createToastContainer() {
            const container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
            return container;
        }
        
        function getToastIcon(type) {
            const icons = {
                'success': 'check-circle',
                'danger': 'exclamation-triangle',
                'warning': 'exclamation-triangle',
                'info': 'info-circle'
            };
            return icons[type] || 'info-circle';
        }
        
        // Validação de formulários
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                            
                            // Focar no primeiro campo inválido
                            const firstInvalid = form.querySelector(':invalid');
                            if (firstInvalid) {
                                firstInvalid.focus();
                                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }
                        } else {
                            // Mostrar loading no botão de submit
                            const submitBtn = form.querySelector('button[type="submit"]');
                            if (submitBtn) {
                                showLoading(submitBtn);
                            }
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
        
        // Auto-hide alerts
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    if (alert) {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }
                }, 7000);
            });
            
            // Inicializar tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Inicializar popovers
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
            
            // Adicionar efeitos de hover em cards
            const cards = document.querySelectorAll('.card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    if (!this.classList.contains('no-hover')) {
                        this.style.transform = 'translateY(-2px)';
                    }
                });
                
                card.addEventListener('mouseleave', function() {
                    if (!this.classList.contains('no-hover')) {
                        this.style.transform = 'translateY(0)';
                    }
                });
            });
            
            // Adicionar animações de entrada
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);
            
            // Observar elementos com animação
            const animatedElements = document.querySelectorAll('.animate-on-scroll');
            animatedElements.forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(30px)';
                el.style.transition = 'all 0.6s ease';
                observer.observe(el);
            });
            
            // Melhorar acessibilidade
            const buttons = document.querySelectorAll('button, .btn');
            buttons.forEach(btn => {
                if (!btn.getAttribute('aria-label') && !btn.textContent.trim()) {
                    const icon = btn.querySelector('i[class*="bi-"]');
                    if (icon) {
                        const iconClass = Array.from(icon.classList).find(cls => cls.startsWith('bi-'));
                        if (iconClass) {
                            btn.setAttribute('aria-label', iconClass.replace('bi-', '').replace('-', ' '));
                        }
                    }
                }
            });
            
            // Adicionar indicador de loading global
            const loadingIndicator = document.createElement('div');
            loadingIndicator.id = 'global-loading';
            loadingIndicator.innerHTML = `
                <div class="d-flex align-items-center justify-content-center position-fixed top-0 start-0 w-100 h-100" 
                     style="background: rgba(255,255,255,0.9); backdrop-filter: blur(5px); z-index: 9999; display: none;">
                    <div class="text-center">
                        <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;"></div>
                        <div class="fw-bold">Carregando...</div>
                    </div>
                </div>
            `;
            document.body.appendChild(loadingIndicator);
        });
        
        // Função para mostrar loading global
        function showGlobalLoading() {
            const loading = document.querySelector('#global-loading > div');
            if (loading) {
                loading.style.display = 'flex';
            }
        }
        
        // Função para esconder loading global
        function hideGlobalLoading() {
            const loading = document.querySelector('#global-loading > div');
            if (loading) {
                loading.style.display = 'none';
            }
        }
        
        // Interceptar formulários para mostrar loading
        document.addEventListener('submit', function(e) {
            if (e.target.tagName === 'FORM' && !e.target.classList.contains('no-loading')) {
                setTimeout(showGlobalLoading, 100);
            }
        });
        
        // Esconder loading quando a página carregar
        window.addEventListener('load', function() {
            hideGlobalLoading();
        });
        
        // Função para copiar texto para clipboard
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                showToast('Texto copiado para a área de transferência!', 'success');
            }).catch(function() {
                showToast('Erro ao copiar texto', 'danger');
            });
        }
        
        // Função para validar CPF
        function validateCPF(cpf) {
            cpf = cpf.replace(/[^\d]+/g, '');
            if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;
            
            let sum = 0;
            for (let i = 0; i < 9; i++) {
                sum += parseInt(cpf.charAt(i)) * (10 - i);
            }
            let remainder = (sum * 10) % 11;
            if (remainder === 10 || remainder === 11) remainder = 0;
            if (remainder !== parseInt(cpf.charAt(9))) return false;
            
            sum = 0;
            for (let i = 0; i < 10; i++) {
                sum += parseInt(cpf.charAt(i)) * (11 - i);
            }
            remainder = (sum * 10) % 11;
            if (remainder === 10 || remainder === 11) remainder = 0;
            return remainder === parseInt(cpf.charAt(10));
        }
        
        // Função para validar email
        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
        
        // Função para formatar telefone
        function formatPhone(phone) {
            phone = phone.replace(/\D/g, '');
            if (phone.length === 11) {
                return phone.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            } else if (phone.length === 10) {
                return phone.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
            }
            return phone;
        }
        
        // Função para formatar CPF
        function formatCPF(cpf) {
            cpf = cpf.replace(/\D/g, '');
            return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
        }
        
        // Adicionar máscaras automáticas
        document.addEventListener('input', function(e) {
            if (e.target.dataset.mask === 'cpf') {
                e.target.value = formatCPF(e.target.value);
            } else if (e.target.dataset.mask === 'phone') {
                e.target.value = formatPhone(e.target.value);
            }
        });
    </script>
</body>
</html>