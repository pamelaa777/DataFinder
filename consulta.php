<?php
// Conexão com o banco de dados (ajuste os parâmetros de conexão conforme necessário)
$host = 'localhost';   // Substitua pelo seu servidor de banco de dados
$dbname = 'a2023952489@teiacoltec.org'; // Nome do banco de dados
$username = 'a2023952489@teiacoltec.org'; // Nome de usuário
$password = '@Coltec2024'; // Senha do banco de dados

$conn = new mysqli($host, $username, $password, $dbname);

// Verifica se a conexão foi bem-sucedida
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Função para executar as consultas e retornar os resultados
function executarConsulta($query) {
    global $conn;
    $result = $conn->query($query);
    return $result;
}

// Função para listar departamentos
function listarDepartamentos($conn) {
    $sql = "SELECT Nome FROM DEPARTAMENTO";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Função para listar projetos
function listarProjetos($conn) {
    $sql = "SELECT Nome, Numero FROM PROJETO";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

$departamentos = listarDepartamentos($conn);
$projetos = listarProjetos($conn);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultas de Departamento</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #4B0082; /* Lilás escuro */
            color: #000; /* Preto */
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #9370DB; /* Lilás claro */
            color: white;
        }
        tr:nth-child(even) {
            background-color: #D8BFD8; /* Lilás muito claro */
        }
        button {
            background-color: #9370DB; /* Lilás claro */
            color: white;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            margin-top: 10px;
        }
        button:hover {
            background-color: #BA55D3; /* Lilás médio */
        }
        .form-container {
            background-color: #D8BFD8; /* Lilás muito claro */
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            color: #000; /* Preto */
        }
        h1 {
            color: #fff;
        }
        h2, h3 {
            color: #000; /* Preto */
        }
        label, select {
            color: #000; /* Preto */
        }
    </style>
</head>
<body>

<h1>Consultas de Departamento</h1>

<!-- Botão de Voltar -->
<button onclick="window.location.href='listagem.php'">Voltar para a Página Inicial</button>

<!-- Maior e Menor Salário de um Departamento -->
<div class="form-container">
    <h2>Maior e Menor Salário de um Departamento</h2>
    <form method="GET" action="">
        <label for="departamento">Departamento:</label>
        <select name="departamento" id="departamento" required>
            <?php foreach ($departamentos as $departamento): ?>
                <option value="<?php echo $departamento['Nome']; ?>"><?php echo $departamento['Nome']; ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Consultar</button>
    </form>

    <?php
    if (isset($_GET['departamento'])) {
        $departamento = $_GET['departamento'];

        $query = "
            SELECT D.Nome AS Departamento, MAX(F.Salario) AS Maior_Salario, MIN(F.Salario) AS Menor_Salario
            FROM DEPARTAMENTO D
            JOIN FUNCIONARIO F ON D.Numero = F.DepartamentoNumero
            WHERE D.Nome = '$departamento'
            GROUP BY D.Nome
        ";

        $result = executarConsulta($query);
        if ($result->num_rows > 0) {
            echo "<table><tr><th>Departamento</th><th>Maior Salário</th><th>Menor Salário</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr><td>" . $row['Departamento'] . "</td><td>" . $row['Maior_Salario'] . "</td><td>" . $row['Menor_Salario'] . "</td></tr>";
            }
            echo "</table>";
        } else {
            echo "Nenhum resultado encontrado.";
        }
    }
    ?>
</div>

<!-- Salário Médio de um Departamento -->
<div class="form-container">
    <h2>Salário Médio de um Departamento</h2>
    <form method="GET" action="">
        <label for="departamento_media">Departamento:</label>
        <select name="departamento_media" id="departamento_media" required>
            <?php foreach ($departamentos as $departamento): ?>
                <option value="<?php echo $departamento['Nome']; ?>"><?php echo $departamento['Nome']; ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Consultar</button>
    </form>

    <?php
    if (isset($_GET['departamento_media'])) {
        $departamento = $_GET['departamento_media'];

        $query = "
            SELECT D.Nome AS Departamento, AVG(F.Salario) AS Salario_Medio
            FROM DEPARTAMENTO D
            JOIN FUNCIONARIO F ON D.Numero = F.DepartamentoNumero
            WHERE D.Nome = '$departamento'
            GROUP BY D.Nome
        ";

        $result = executarConsulta($query);
        if ($result->num_rows > 0) {
            echo "<table><tr><th>Departamento</th><th>Salário Médio</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr><td>" . $row['Departamento'] . "</td><td>" . $row['Salario_Medio'] . "</td></tr>";
            }
            echo "</table>";
        } else {
            echo "Nenhum resultado encontrado.";
        }
    }
    ?>
</div>

<!-- Salário Médio da Empresa -->
<div class="form-container">
    <h2>Salário Médio da Empresa</h2>
    <form method="GET" action="">
        <button type="submit" name="salario_medio_empresa">Consultar</button>
    </form>

    <?php
    if (isset($_GET['salario_medio_empresa'])) {
        $query = "
            SELECT AVG(Salario) AS Salario_Medio_Empresa
            FROM FUNCIONARIO
        ";

        $result = executarConsulta($query);
        if ($result->num_rows > 0) {
            echo "<table><tr><th>Salário Médio da Empresa</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr><td>" . $row['Salario_Medio_Empresa'] . "</td></tr>";
            }
            echo "</table>";
        } else {
            echo "Nenhum resultado encontrado.";
        }
    }
    ?>
</div>

<!-- Número de Empregados de um Departamento -->
<div class="form-container">
    <h2>Número de Empregados de um Departamento</h2>
    <form method="GET" action="">
        <label for="departamento_empregados">Departamento:</label>
        <select name="departamento_empregados" id="departamento_empregados" required>
            <?php foreach ($departamentos as $departamento): ?>
                <option value="<?php echo $departamento['Nome']; ?>"><?php echo $departamento['Nome']; ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Consultar</button>
    </form>

    <?php
    if (isset($_GET['departamento_empregados'])) {
        $departamento = $_GET['departamento_empregados'];

        $query = "
            SELECT D.Nome AS Departamento, COUNT(F.Cpf) AS Numero_Empleados
            FROM DEPARTAMENTO D
            JOIN FUNCIONARIO F ON D.Numero = F.DepartamentoNumero
            WHERE D.Nome = '$departamento'
            GROUP BY D.Nome
        ";

        $result = executarConsulta($query);
        if ($result->num_rows > 0) {
            echo "<table><tr><th>Departamento</th><th>Número de Empregados</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr><td>" . $row['Departamento'] . "</td><td>" . $row['Numero_Empleados'] . "</td></tr>";
            }
            echo "</table>";
        } else {
            echo "Nenhum resultado encontrado.";
        }
    }
    ?>
</div>

<!-- Projetos Administrados por um Departamento -->
<div class="form-container">
    <h2>Projetos Administrados por um Departamento</h2>
    <form method="GET" action="">
        <label for="departamento_projetos">Departamento:</label>
        <select name="departamento_projetos" id="departamento_projetos" required>
            <?php foreach ($departamentos as $departamento): ?>
                <option value="<?php echo $departamento['Nome']; ?>"><?php echo $departamento['Nome']; ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Consultar</button>
    </form>

    <?php
    if (isset($_GET['departamento_projetos'])) {
        $departamento = $_GET['departamento_projetos'];

        $query = "
            SELECT P.Nome AS Projeto
            FROM DEPARTAMENTO D
            JOIN CONTROLA C ON D.Nome = C.DepartamentoNome
            JOIN PROJETO P ON C.TipoProjeto = P.Nome
            WHERE D.Nome = '$departamento'
        ";

        $result = executarConsulta($query);
        if ($result->num_rows > 0) {
            echo "<table><tr><th>Projeto</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr><td>" . $row['Projeto'] . "</td></tr>";
            }
            echo "</table>";
        } else {
            echo "Nenhum projeto encontrado.";
        }
    }
    ?>
</div>

<!-- Empregados Trabalhando em um Projeto -->
<div class="form-container">
    <h2>Empregados Trabalhando em um Projeto</h2>
    <form method="GET" action="">
        <label for="projeto_empregados">Projeto:</label>
        <select name="projeto_empregados" id="projeto_empregados" required>
            <?php foreach ($projetos as $projeto): ?>
                <option value="<?php echo $projeto['Numero']; ?>"><?php echo $projeto['Nome']; ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Consultar</button>
    </form>

    <?php
    if (isset($_GET['projeto_empregados'])) {
        $projetoNumero = $_GET['projeto_empregados'];

        $query = "
            SELECT F.Pnome, F.Unome
            FROM FUNCIONARIO F
            JOIN TRABALHA_EM TE ON F.Cpf = TE.FuncionarioCpf
            WHERE TE.ProjetoNumero = :projetoNumero
        ";

        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $projetoNumero);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "<table><tr><th>Nome do Empregado</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr><td>" . htmlspecialchars($row['Pnome']) . " " . htmlspecialchars($row['Unome']) . "</td></tr>";
            }
            echo "</table>";
        } else {
            echo "Nenhum empregado encontrado.";
        }
    }
    ?>

    <!-- Botões para cada projeto -->
    <div>
        <h3>Projetos Disponíveis</h3>
        <?php foreach ($projetos as $projeto): ?>
            <form method="GET" action="">
                <input type="hidden" name="projeto_empregados" value="<?php echo htmlspecialchars($projeto['Numero']); ?>">
                <button type="submit"><?php echo htmlspecialchars($projeto['Nome']); ?></button>
            </form>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>

<?php
$conn->close();
?>