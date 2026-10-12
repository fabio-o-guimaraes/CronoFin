/* Gráfico de receitas e despesas (secção Resumo) */
(function () {
  const canvas = document.getElementById("chart-income-expenses");
  const dataElement = document.getElementById("chart-data");

  /* Se falta algum elemento ou a biblioteca não carregou, não faz nada */
  if (!canvas || !dataElement || typeof Chart === "undefined") {
    return;
  }

  /* Dados calculados pelo PHP */
  const data = JSON.parse(dataElement.textContent);

  /* Formatação de euros, ao estilo português */
  const euro = new Intl.NumberFormat("pt-PT", {
    style: "currency",
    currency: "EUR",
  });

  /* Cores e fonte vêm das variáveis do CSS, para ficarem iguais ao resto do site */
  const styles = getComputedStyle(document.documentElement);
  const colorPrimary = styles.getPropertyValue("--color-primary").trim();
  const colorDanger = styles.getPropertyValue("--color-danger").trim();

  Chart.defaults.font.family = styles
    .getPropertyValue("--font-family-base")
    .trim();
  Chart.defaults.color = styles.getPropertyValue("--color-dark").trim();

  new Chart(canvas, {
    type: "bar",
    data: {
      labels: data.labels,
      datasets: [
        {
          label: "Receitas",
          data: data.income,
          backgroundColor: colorPrimary,
          borderRadius: 4,
        },
        {
          label: "Despesas",
          data: data.expenses,
          backgroundColor: colorDanger,
          borderRadius: 4,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: "bottom" },
        tooltip: {
          callbacks: {
            label: function (context) {
              return (
                context.dataset.label + ": " + euro.format(context.parsed.y)
              );
            },
          },
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function (value) {
              return euro.format(value);
            },
          },
        },
      },
    },
  });
})();
