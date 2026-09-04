    </main>

    <!-- Footer Institucional GobCL -->
    <footer class="footer-gobcl">
        <div class="container-fluid px-4 text-center">
            <div class="row align-items-center">
                <div class="col-md-6 text-md-start mb-2 mb-md-0">
                    <strong>Gobierno de Chile</strong> &bull; Ministerio de Educación &bull; PROYECTO DE TÍTULO CFTCENCO
                </div>
                <div class="col-md-6 text-md-end">
                    <small>Sistema de Inventario Tecnológico Escolar &copy; <?= date('Y') ?></small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Script Alertas GobCL -->
    <script src="/gestionInv/assets/js/script_alertas.js"></script>

    <?php
    // Mostrar alertas flash de SweetAlert2 si existen
    if (function_exists('mostrar_alerta')) {
        mostrar_alerta();
    }
    ?>
</body>
</html>
