<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CronoFin - Gestão de Finanças Pessoais</title>

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <!-- <link rel="stylesheet" href="assets/css/pages/profile.css"> editar caso use css especifico em certas páginas-->
</head>

<body>
    <?php require_once '../includes/header.php'; ?>

    <main class="home-main">

        <!-- Hero section -->
        <section class="hero">
            <h1>Descobre para onde vai o teu dinheiro com o CronoFin</h1>
            <p>Organiza as tuas receitas e despesas de forma simples e começa a construir hábitos financeiros melhores.</p>
            <img class="hero-img" src="assets/img/porquinho.jpg" alt="Exemplo dashboard da aplicação"> <!-- alterar para print do dashboard -->
            <a href="register.php" class="btn btn-primary">Criar conta</a>
        </section>

        <!-- Benefits section -->
        <section class="benefits">
            <h2>As tuas finanças de forma simples</h2>

            <div class="benefits-grid">
                <article class="benefit-card">
                    <img src="assets/img/goal.jpg" alt="Alvo" class="benefit-icon">
                    <h3>Controlo</h3>
                    <p>Regista receitas e despesas com total clareza.</p>
                </article>

                <article class="benefit-card">
                    <img src="assets/img/maquina.jpg" alt="Maquina de calcular" class="benefit-icon">
                    <h3>Organização</h3>
                    <p>Categoriza os teus movimentos, sem folhas de cálculo complicadas.</p>
                </article>

                <article class="benefit-card">
                    <img src="assets/img/moedas.jpg" alt="Acumulação de moedas" class="benefit-icon">
                    <h3>Progresso</h3>
                    <p>Define objetivos de poupança e acompanha o teu caminho.</p>
                </article>
            </div>
        </section>

        <!-- Start now section -->
        <section class="cta-section">
            <h2>Dá o primeiro passo</h2>
            <p>Sem folhas de cálculo, sem complicações - só tu e as tuas finanças, organizadas num só lugar.</p>
            <a href="register.php" class="btn btn-primary">Começar agora</a>
        </section>

    </main>

    <?php require_once '../includes/footer.php'; ?>
</body>

</html>