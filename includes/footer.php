<!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-store"></i> Marketplace Digital</h5>
                    <p class="mb-0">A melhor plataforma para produtos digitais do Brasil.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> Marketplace Digital. Todos os direitos reservados.</p>
                    <p class="mb-0">
                        <a href="#" class="text-white-50 text-decoration-none">Termos de Uso</a> | 
                        <a href="#" class="text-white-50 text-decoration-none">Política de Privacidade</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo isset($js_path) ? $js_path : 'assets/js/script.js'; ?>"></script>
</body>
</html>