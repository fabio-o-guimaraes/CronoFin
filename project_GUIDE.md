# 📋 Guia de desenvolvimento - Projeto CronoFin
---

## 🌐 Visão Geral
---
- **Tema:** Gestor de finanças pessoais
- **Nome:** CronoFin
- **Finalidade:** Projeto de desenvolvimento pessoal
- **Objetivo:** Criar um projeto que permita, de forma simples, ajudar o utilizador a desenvolver hábitos financeiros saudáveis.
- **Tecnologias:** HTML, CSS, Bootstrap, JavaScript, PHP, MySQL

---

## 🎯 Briefing
---
- **Objetivo do website:** Desenvolver uma aplicação web que permita ao utilizador gerir as suas finanças pessoais, acompanhando o dinheiro que entra e sai através de uma interface simples, intuitiva e orientada para a criação de hábitos financeiros saudáveis.

- **Público-alvo:** Adultos entre os 20 e os 45 anos que pretendem organizar as suas finanças pessoais de forma simples, sem recorrer a aplicações financeiras complexas ou folhas de cálculo.

### Necessidades

- Controlar receitas e despesas;
- Saber para onde vai o dinheiro;
- Poupar para objetivos pessoais;
- Ter uma visão simples da sua situação financeira;
- Substituir o Excel por uma aplicação mais intuitiva.

### Exemplos de utilizadores

- Jovens trabalhadores;
- Casais;
- Estudantes universitários;
- Pessoas que começaram a gerir o seu próprio dinheiro;
- Utilizadores que atualmente recorrem ao Excel.

### Princípios do projeto

- Interface simples e intuitiva;
- Poucos cliques para executar tarefas;
- Informação clara e objetiva;
- Dashboard de fácil leitura;
- Apenas funcionalidades essenciais;
- Navegação simples e consistente;
- Promover hábitos financeiros saudáveis.

### Sites de referência

| Aplicação | Pontos fortes | Pontos fracos |
|---|---|---|
| **YNAB** | - Interface limpa, todos os elementos "respiram";<br>- Exibe a informação mais relevante imediatamente (quanto tenho, quanto posso gastar, quanto já gastei);<br>- Formulários rápidos, com poucos cliques se chega ao resultado. | - Curva de aprendizagem;<br>- Parece uma ferramenta de trabalho, fica muito séria e não transmite sensação de leveza;<br>- Muitas opções e demasiada informação, tornando a utilização menos simples e direta. |
| **Firefly III** | - Excelente estrutura/arquitetura. | - Demasiadas opções, sobretudo para novos utilizadores;<br>- Interface pesada, com muitos textos, tabelas e menus, tendo pouco espaço livre. |

### Conclusão da análise

O projeto procurará combinar a simplicidade visual e a boa experiência de utilização do YNAB com uma arquitetura organizada, evitando, no entanto, a complexidade funcional presente em ambas as aplicações.

**O problema:** Muitas aplicações de gestão financeira apresentam um elevado nível de complexidade para utilizadores que apenas pretendem controlar as suas receitas e despesas diárias.

**A solução:** Criar uma aplicação web simples, intuitiva e rápida, que permita ao utilizador acompanhar receitas e despesas sem necessidade de aprender métodos financeiros complexos.

---

## 🗺️ Mapa do site
---
![Mapa do site do CronoFin](docs/design/sitemap_&_er_database/Site_Map.jpg)

### Área pública

**Página Inicial**  
Objetivo: convencer o utilizador a experimentar a aplicação, comunicando de forma simples os seus benefícios e funcionalidades principais.

- Hero
- Benefícios
- Como funciona
- Screenshots
- Call to Action

**Registo**

**Login**

### Área privada

Restrita a utilizadores com sessão iniciada.

**Dashboard**
- Resumo com saldo atual, receitas, despesas e poupança;
- Gráfico simples de receitas vs. despesas;
- Últimos movimentos.

**Movimentos**
- Pesquisa;
- Tabela com data, categoria, descrição, valor e tipo;
- Adicionar movimento através de formulário.

**Categorias**
- Listar;
- Adicionar;
- Editar;
- Eliminar/desativar;
- Categorias iniciais: alimentação, transporte, casa, lazer e saúde.

**Objetivos**
- Criar novo objetivo;
- Definir nome e valor;
- Adicionar descrição opcional;
- Visualizar progresso através de uma barra de percentagem;
- Adicionar valores poupados ao objetivo.

**Perfil**
- Dados pessoais;
- Alteração de password;
- Preferências;
- Nome, email e fotografia.

**Logout**

---

## ✏️ Design/Wireframes
---
### Versão Mobile
![Wireframe da homepage - versão mobile](docs/design/wireframes/Wireframe_home_page_mobile.jpg)<br>
![Wireframe do dashboard - versão mobile](docs/design/wireframes/Dashboard_mobile_version.jpg)

### Versão Desktop
![Wireframe do dashboard - versão desktop](docs/design/wireframes/Wireframe_home_page_desktop.jpg)
![Wireframe da homepage - versão desktop](docs/design/wireframes/Dashboard_desktop_version.jpg)

Foram desenvolvidos wireframes de baixa fidelidade para as duas principais áreas da aplicação: a homepage e o dashboard.

A homepage foi concebida para apresentar a aplicação ao utilizador, destacar os seus principais benefícios e incentivá-lo a criar uma conta para começar a utilizar. Na versão mobile, a principal diferença relativamente à versão desktop prende-se com a reorganização dos elementos numa disposição vertical, proporcionando uma melhor experiência de utilização em ecrãs de menor dimensão.

O dashboard foi concebido com uma estrutura semelhante à utilizada em aplicações como o Gmail, recorrendo a um menu lateral para a navegação entre as diferentes funcionalidades. Na versão desktop, este menu permanece sempre visível, enquanto na versão mobile é substituído por um botão do tipo "hambúrguer", permitindo aproveitar melhor o espaço disponível no ecrã.

---

## 🎨 Conteúdo & Identidade visual
---
### Logotipo
Foram desenvolvidas duas versões, para fundos claros e escuros <br>
![Logotipo - claro](docs/design/logos/Icon_v2.svg)
![Logotipo - escuro](docs/design/logos/Icon_v1.svg) <br>
![Logotipo - texto claro](docs/design/logos/CronoFin_v1.svg)
![Logotipo - texto escuro](docs/design/logos/CronoFin_v2.svg)

### Moodboard
Quero transmitir:
- [x] Simplicidade – Conseguir entender e utilizar sem ter que aprender a aplicação
- [x] Controlo/organização – Saber para onde está a ir o meu dinheiro
- [x] Progresso/Crescimento – Definir planos de ação que permitam juntar para determinados objetivos

![Moodboard](docs/design/moodboard_&_styleguide/Moodboard.jpg)

### Style Guide

Como apoio ao desenvolvimento do website, foi criado um Style Guide que define a identidade visual, tipografia, sistema de cores, botões e iconografia, garantindo consistência visual e facilitando a implementação em HTML e CSS.

Estas definições permitem manter uma identidade visual consistente em todas as páginas, contribuindo para um website responsivo, intuitivo e coerente com os objetivos da marca.

![Style Guide](docs/design/moodboard_&_styleguide/StyleGuide.jpg)

---

## Base de dados & arquitectura

### Desenho da base de dados
![Modelo ER da base de dados](docs/design/sitemap_&_er_database/ER_data_base.jpg.png) 

![Diagrama da base de dados](docs/design/sitemap_&_er_database/diagrama_base_dados.png)

---



