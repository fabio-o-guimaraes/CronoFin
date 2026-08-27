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



