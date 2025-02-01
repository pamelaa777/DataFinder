<?php
$host = 'localhost';   // Substitua pelo seu servidor de banco de dados
$dbname = 'a2023952489@teiacoltec.org'; // Nome do banco de dados
$username = 'a2023952489@teiacoltec.org'; // Nome de usuário
$password = '@Coltec2024'; // Senha do banco de dados

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Erro ao conectar ao banco de dados: ' . $e->getMessage();
    exit;
}

// Função para listar funcionários
function listarFuncionarios($pdo) {
    $sql = "SELECT Cpf, Pnome, Minicial, Unome, DataNasc, Endereco, Sexo, Salario, SupervisorID, DepartamentoNumero, ProjetoNumero FROM FUNCIONARIO";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Função para listar dependentes
function listarDependentes($pdo) {
    $sql = "SELECT ID, Nome, Sexo, DataNascimento, Parentesco, FuncionarioCpf FROM DEPENDENTE";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Função para listar departamentos
function listarDepartamentos($pdo) {
    $sql = "SELECT GerenteCpf, Nome, Localizacao, Numero FROM DEPARTAMENTO";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Função para listar projetos
function listarProjetos($pdo) {
    $sql = "SELECT P.Nome, P.Localizacao
            FROM PROJETO P
            JOIN CONTROLA C ON P.Nome = C.TipoProjeto
            JOIN DEPARTAMENTO D ON C.DepartamentoNome = D.Nome";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Função para adicionar funcionário
function adicionarFuncionario($pdo, $cpf, $pnome, $minicial, $unome, $dataNasc, $endereco, $sexo, $salario, $supervisorID, $departamentoNumero, $projetoNumero) {
    try {
        $pdo->beginTransaction();

        // Adiciona o funcionário na tabela FUNCIONARIO
        $sql = "INSERT INTO FUNCIONARIO (Cpf, Pnome, Minicial, Unome, DataNasc, Endereco, Sexo, Salario, SupervisorID, DepartamentoNumero, ProjetoNumero) 
                VALUES (:cpf, :pnome, :minicial, :unome, :dataNasc, :endereco, :sexo, :salario, :supervisorID, :departamentoNumero, :projetoNumero)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':cpf', $cpf);
        $stmt->bindParam(':pnome', $pnome);
        $stmt->bindParam(':minicial', $minicial);
        $stmt->bindParam(':unome', $unome);
        $stmt->bindParam(':dataNasc', $dataNasc);
        $stmt->bindParam(':endereco', $endereco);
        $stmt->bindParam(':sexo', $sexo);
        $stmt->bindParam(':salario', $salario);
        $stmt->bindParam(':supervisorID', $supervisorID);
        $stmt->bindParam(':departamentoNumero', $departamentoNumero);
        $stmt->bindParam(':projetoNumero', $projetoNumero);
        $stmt->execute();

        // Adiciona o funcionário na tabela TRABALHA_EM
        if ($departamentoNumero) {
            $sql = "INSERT INTO TRABALHA_EM (FuncionarioCpf, DepartamentoNome, Horas) 
                    VALUES (:cpf, (SELECT Nome FROM DEPARTAMENTO WHERE Numero = :departamentoNumero), 40)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':cpf', $cpf);
            $stmt->bindParam(':departamentoNumero', $departamentoNumero);
            $stmt->execute();
        }

        // Adiciona o funcionário na tabela TRABALHA_PARA
        if ($projetoNumero) {
            $sql = "INSERT INTO TRABALHA_PARA (FuncionarioCpf, TipoProjeto, DataInicio, Numero_Funcionario) 
                    VALUES (:cpf, (SELECT Nome FROM PROJETO WHERE Numero = :projetoNumero), CURDATE(), 
                    (SELECT COUNT(*) + 1 FROM (SELECT * FROM TRABALHA_PARA) AS temp WHERE TipoProjeto = (SELECT Nome FROM PROJETO WHERE Numero = :projetoNumero)))";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':cpf', $cpf);
            $stmt->bindParam(':projetoNumero', $projetoNumero);
            $stmt->execute();
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// Função para adicionar dependente
function adicionarDependente($pdo, $nome, $sexo, $dataNascimento, $parentesco, $funcionarioCpf) {
    $sql = "INSERT INTO DEPENDENTE (Nome, Sexo, DataNascimento, Parentesco, FuncionarioCpf) 
            VALUES (:nome, :sexo, :dataNascimento, :parentesco, :funcionarioCpf)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':sexo', $sexo);
    $stmt->bindParam(':dataNascimento', $dataNascimento);
    $stmt->bindParam(':parentesco', $parentesco);
    $stmt->bindParam(':funcionarioCpf', $funcionarioCpf);
    $stmt->execute();
}

// Função para adicionar departamento
function adicionarDepartamento($pdo, $gerenteCpf, $nome, $localizacao) {
    // Verifica se o departamento já existe
    $sql = "SELECT COUNT(*) FROM DEPARTAMENTO WHERE Nome = :nome";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':nome', $nome);
    $stmt->execute();
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        echo "Erro: Departamento já existe.";
        return;
    }

    // Adiciona o departamento no banco de dados
    $sql = "INSERT INTO DEPARTAMENTO (Nome, GerenteCpf, Localizacao) VALUES (:nome, :gerenteCpf, :localizacao)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':gerenteCpf', $gerenteCpf);
    $stmt->bindParam(':localizacao', $localizacao);
    $stmt->execute();
}

// Função para adicionar projeto
function adicionarProjeto($pdo, $nome, $localizacao) {
    // Adiciona o projeto no banco de dados
    $sql = "INSERT INTO PROJETO (Nome, Localizacao) VALUES (:nome, :localizacao)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':localizacao', $localizacao);
    $stmt->execute();
}

// Função para excluir funcionário
function excluirFuncionario($pdo, $cpf) {
    // Excluir o funcionário
    $sql = "DELETE FROM FUNCIONARIO WHERE Cpf = :cpf";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':cpf', $cpf);
    $stmt->execute();
}

// Função para excluir dependente
function excluirDependente($pdo, $id) {
    $sql = "DELETE FROM DEPENDENTE WHERE ID = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
}

// Função para excluir departamento
function excluirDepartamento($pdo, $nome) {
    $sql = "DELETE FROM DEPARTAMENTO WHERE Nome = :nome";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':nome', $nome);
    $stmt->execute();
}

// Função para excluir projeto
function excluirProjeto($pdo, $nome) {
    $sql = "DELETE FROM PROJETO WHERE Nome = :nome";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':nome', $nome);
    $stmt->execute();
}

// Função para editar funcionário
function editarFuncionario($pdo, $cpf, $pnome, $minicial, $unome, $dataNasc, $endereco, $sexo, $salario, $supervisorID, $departamentoNumero, $projetoNumero) {
    $sql = "UPDATE FUNCIONARIO SET Pnome = :pnome, Minicial = :minicial, Unome = :unome, DataNasc = :dataNasc, Endereco = :endereco, Sexo = :sexo, Salario = :salario, SupervisorID = :supervisorID, DepartamentoNumero = :departamentoNumero, ProjetoNumero = :projetoNumero WHERE Cpf = :cpf";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':cpf', $cpf);
    $stmt->bindParam(':pnome', $pnome);
    $stmt->bindParam(':minicial', $minicial);
    $stmt->bindParam(':unome', $unome);
    $stmt->bindParam(':dataNasc', $dataNasc);
    $stmt->bindParam(':endereco', $endereco);
    $stmt->bindParam(':sexo', $sexo);
    $stmt->bindParam(':salario', $salario);
    $stmt->bindParam(':supervisorID', $supervisorID);
    $stmt->bindParam(':departamentoNumero', $departamentoNumero);
    $stmt->bindParam(':projetoNumero', $projetoNumero);
    $stmt->execute();
}

// Função para editar dependente
function editarDependente($pdo, $id, $novoNome, $sexo, $dataNascimento, $parentesco) {
    $sql = "UPDATE DEPENDENTE 
            SET Nome = :novoNome, Sexo = :sexo, DataNascimento = :dataNascimento, Parentesco = :parentesco
            WHERE ID = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':novoNome', $novoNome);
    $stmt->bindParam(':sexo', $sexo);
    $stmt->bindParam(':dataNascimento', $dataNascimento);
    $stmt->bindParam(':parentesco', $parentesco);
    $stmt->execute();
}

// Função para editar departamento
function editarDepartamento($pdo, $nome, $localizacao, $gerenteCpf) {
    $sql = "UPDATE DEPARTAMENTO SET Localizacao = :localizacao, GerenteCpf = :gerenteCpf WHERE Nome = :nome";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':localizacao', $localizacao);
    $stmt->bindParam(':gerenteCpf', $gerenteCpf);
    $stmt->execute();
}

// Função para editar projeto
function editarProjeto($pdo, $nome, $localizacao) {
    $sql = "UPDATE PROJETO SET Localizacao = :localizacao WHERE Nome = :nome";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':localizacao', $localizacao);
    $stmt->execute();
}

$acao = $_GET['acao'] ?? '';
$cpfFuncionario = $_GET['cpf'] ?? '';
$gerenteCpf = $_GET['gerenteCpf'] ?? '';
$nomeProjeto = $_GET['nome'] ?? '';
$idDependente = $_GET['id'] ?? '';

// Adicionar funcionário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $acao === 'adicionarFuncionario') {
    $cpf = $_POST['cpf'];
    $pnome = $_POST['pnome'];
    $minicial = $_POST['minicial'];
    $unome = $_POST['unome'];
    $dataNasc = $_POST['dataNasc'];
    $endereco = $_POST['endereco'];
    $sexo = $_POST['sexo'];
    $salario = $_POST['salario'];
    $supervisorID = $_POST['supervisorID'] ?? null;
    $departamentoNumero = $_POST['departamentoNumero'] ?? null;
    $projetoNumero = $_POST['projetoNumero'] ?? null;
    adicionarFuncionario($pdo, $cpf, $pnome, $minicial, $unome, $dataNasc, $endereco, $sexo, $salario, $supervisorID, $departamentoNumero, $projetoNumero);
}

// Adicionar dependente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $acao === 'adicionarDependente') {
    $nome = $_POST['nome'];
    $sexo = $_POST['sexo'];
    $dataNascimento = $_POST['dataNascimento'];
    $parentesco = $_POST['parentesco'];
    $cpfFuncionario = $_POST['cpfFuncionario'];
    adicionarDependente($pdo, $nome, $sexo, $dataNascimento, $parentesco, $cpfFuncionario);
}

// Adicionar departamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $acao === 'adicionarDepartamento') {
    $gerenteCpf = $_POST['gerenteCpf'];
    $nome = $_POST['nome'];
    $localizacao = $_POST['localizacao'];
    adicionarDepartamento($pdo, $gerenteCpf, $nome, $localizacao);
}

// Adicionar projeto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $acao === 'adicionarProjeto') {
    $nome = $_POST['nome'];
    $localizacao = $_POST['localizacao'];
    adicionarProjeto($pdo, $nome, $localizacao);
}

// Excluir funcionário
if ($acao === 'excluirFuncionario' && $cpfFuncionario) {
    excluirFuncionario($pdo, $cpfFuncionario);
}

// Excluir dependente
if ($acao === 'excluirDependente' && $idDependente) {
    excluirDependente($pdo, $idDependente);
}

// Excluir departamento
if ($acao === 'excluirDepartamento' && $nome) {
    excluirDepartamento($pdo, $nome);
}

// Excluir projeto
if ($acao === 'excluirProjeto' && $nomeProjeto) {
    excluirProjeto($pdo, $nomeProjeto);
}

// Editar funcionário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $acao === 'editarFuncionario') {
    $cpf = $_POST['cpf'];
    $pnome = $_POST['pnome'];
    $minicial = $_POST['minicial'];
    $unome = $_POST['unome'];
    $dataNasc = $_POST['dataNasc'];
    $endereco = $_POST['endereco'];
    $sexo = $_POST['sexo'];
    $salario = $_POST['salario'];
    $supervisorID = $_POST['supervisorID'] ?? null;
    $departamentoNumero = $_POST['departamentoNumero'] ?? null;
    $projetoNumero = $_POST['projetoNumero'] ?? null;
    editarFuncionario($pdo, $cpf, $pnome, $minicial, $unome, $dataNasc, $endereco, $sexo, $salario, $supervisorID, $departamentoNumero, $projetoNumero);
}

// Editar dependente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $acao === 'editarDependente') {
    $id = $_POST['id']; // ID do dependente (chave primária)
    $novoNome = $_POST['novoNome'];
    $sexo = $_POST['sexo'];
    $dataNascimento = $_POST['dataNascimento'];
    $parentesco = $_POST['parentesco'];

    editarDependente($pdo, $id, $novoNome, $sexo, $dataNascimento, $parentesco);
}

// Editar departamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $acao === 'editarDepartamento') {
    $nomeAtual = $_POST['nomeAtual'];
    $nome = $_POST['nome'];
    $localizacao = $_POST['localizacao'];
    $gerenteCpf = $_POST['gerenteCpf'];
    editarDepartamento($pdo, $nomeAtual, $localizacao, $gerenteCpf);
}

// Editar projeto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $acao === 'editarProjeto') {
    $nome = $_POST['nome'];
    $localizacao = $_POST['localizacao'];
    editarProjeto($pdo, $nome, $localizacao);
}
?>



<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listagens</title>
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
            background-color: #fff; /* Branco */
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

<h1>Listar Funcionários, Departamentos e Projetos</h1>

<h2>Funcionários</h2>

<!-- Formulário para selecionar campos -->
<form method="POST" action="">
    <label><input type="checkbox" name="campos[]" value="Cpf" <?php echo in_array('Cpf', $_POST['campos'] ?? ['Cpf', 'Pnome', 'Minicial', 'Unome', 'DataNasc', 'Endereco', 'Sexo', 'Salario', 'SupervisorID', 'DepartamentoNumero', 'ProjetoNumero']) ? 'checked' : ''; ?>> CPF</label>
    <label><input type="checkbox" name="campos[]" value="Pnome" <?php echo in_array('Pnome', $_POST['campos'] ?? ['Cpf', 'Pnome', 'Minicial', 'Unome', 'DataNasc', 'Endereco', 'Sexo', 'Salario', 'SupervisorID', 'DepartamentoNumero', 'ProjetoNumero']) ? 'checked' : ''; ?>> Primeiro Nome</label>
    <label><input type="checkbox" name="campos[]" value="Minicial" <?php echo in_array('Minicial', $_POST['campos'] ?? ['Cpf', 'Pnome', 'Minicial', 'Unome', 'DataNasc', 'Endereco', 'Sexo', 'Salario', 'SupervisorID', 'DepartamentoNumero', 'ProjetoNumero']) ? 'checked' : ''; ?>> Inicial do Meio</label>
    <label><input type="checkbox" name="campos[]" value="Unome" <?php echo in_array('Unome', $_POST['campos'] ?? ['Cpf', 'Pnome', 'Minicial', 'Unome', 'DataNasc', 'Endereco', 'Sexo', 'Salario', 'SupervisorID', 'DepartamentoNumero', 'ProjetoNumero']) ? 'checked' : ''; ?>> Último Nome</label>
    <label><input type="checkbox" name="campos[]" value="DataNasc" <?php echo in_array('DataNasc', $_POST['campos'] ?? ['Cpf', 'Pnome', 'Minicial', 'Unome', 'DataNasc', 'Endereco', 'Sexo', 'Salario', 'SupervisorID', 'DepartamentoNumero', 'ProjetoNumero']) ? 'checked' : ''; ?>> Data de Nascimento</label>
    <label><input type="checkbox" name="campos[]" value="Endereco" <?php echo in_array('Endereco', $_POST['campos'] ?? ['Cpf', 'Pnome', 'Minicial', 'Unome', 'DataNasc', 'Endereco', 'Sexo', 'Salario', 'SupervisorID', 'DepartamentoNumero', 'ProjetoNumero']) ? 'checked' : ''; ?>> Endereço</label>
    <label><input type="checkbox" name="campos[]" value="Sexo" <?php echo in_array('Sexo', $_POST['campos'] ?? ['Cpf', 'Pnome', 'Minicial', 'Unome', 'DataNasc', 'Endereco', 'Sexo', 'Salario', 'SupervisorID', 'DepartamentoNumero', 'ProjetoNumero']) ? 'checked' : ''; ?>> Sexo</label>
    <label><input type="checkbox" name="campos[]" value="Salario" <?php echo in_array('Salario', $_POST['campos'] ?? ['Cpf', 'Pnome', 'Minicial', 'Unome', 'DataNasc', 'Endereco', 'Sexo', 'Salario', 'SupervisorID', 'DepartamentoNumero', 'ProjetoNumero']) ? 'checked' : ''; ?>> Salário</label>
    <label><input type="checkbox" name="campos[]" value="SupervisorID" <?php echo in_array('SupervisorID', $_POST['campos'] ?? ['Cpf', 'Pnome', 'Minicial', 'Unome', 'DataNasc', 'Endereco', 'Sexo', 'Salario', 'SupervisorID', 'DepartamentoNumero', 'ProjetoNumero']) ? 'checked' : ''; ?>> CPF do Supervisor</label>
    <label><input type="checkbox" name="campos[]" value="DepartamentoNumero" <?php echo in_array('DepartamentoNumero', $_POST['campos'] ?? ['Cpf', 'Pnome', 'Minicial', 'Unome', 'DataNasc', 'Endereco', 'Sexo', 'Salario', 'SupervisorID', 'DepartamentoNumero', 'ProjetoNumero']) ? 'checked' : ''; ?>> Número do Departamento</label>
    <label><input type="checkbox" name="campos[]" value="ProjetoNumero" <?php echo in_array('ProjetoNumero', $_POST['campos'] ?? ['Cpf', 'Pnome', 'Minicial', 'Unome', 'DataNasc', 'Endereco', 'Sexo', 'Salario', 'SupervisorID', 'DepartamentoNumero', 'ProjetoNumero']) ? 'checked' : ''; ?>> Número do Projeto</label>
    <button type="submit">Atualizar Campos</button>
</form>

<?php
// Processar a seleção de campos
$camposSelecionados = $_POST['campos'] ?? ['Cpf', 'Pnome', 'Minicial', 'Unome', 'DataNasc', 'Endereco', 'Sexo', 'Salario', 'SupervisorID', 'DepartamentoNumero', 'ProjetoNumero'];
?>

<table>
    <thead>
        <tr>
            <?php if (in_array('Cpf', $camposSelecionados)) echo '<th>CPF</th>'; ?>
            <?php if (in_array('Pnome', $camposSelecionados)) echo '<th>Primeiro Nome</th>'; ?>
            <?php if (in_array('Minicial', $camposSelecionados)) echo '<th>Inicial do Meio</th>'; ?>
            <?php if (in_array('Unome', $camposSelecionados)) echo '<th>Último Nome</th>'; ?>
            <?php if (in_array('DataNasc', $camposSelecionados)) echo '<th>Data de Nascimento</th>'; ?>
            <?php if (in_array('Endereco', $camposSelecionados)) echo '<th>Endereço</th>'; ?>
            <?php if (in_array('Sexo', $camposSelecionados)) echo '<th>Sexo</th>'; ?>
            <?php if (in_array('Salario', $camposSelecionados)) echo '<th>Salário</th>'; ?>
            <?php if (in_array('SupervisorID', $camposSelecionados)) echo '<th>CPF do Supervisor</th>'; ?>
            <?php if (in_array('DepartamentoNumero', $camposSelecionados)) echo '<th>Número do Departamento</th>'; ?>
            <?php if (in_array('ProjetoNumero', $camposSelecionados)) echo '<th>Número do Projeto</th>'; ?>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach (listarFuncionarios($pdo) as $funcionario): ?>
            <tr>
                <?php if (in_array('Cpf', $camposSelecionados)) echo '<td>' . htmlspecialchars($funcionario['Cpf'] ?? '') . '</td>'; ?>
                <?php if (in_array('Pnome', $camposSelecionados)) echo '<td>' . htmlspecialchars($funcionario['Pnome'] ?? '') . '</td>'; ?>
                <?php if (in_array('Minicial', $camposSelecionados)) echo '<td>' . htmlspecialchars($funcionario['Minicial'] ?? '') . '</td>'; ?>
                <?php if (in_array('Unome', $camposSelecionados)) echo '<td>' . htmlspecialchars($funcionario['Unome'] ?? '') . '</td>'; ?>
                <?php if (in_array('DataNasc', $camposSelecionados)) echo '<td>' . htmlspecialchars($funcionario['DataNasc'] ?? '') . '</td>'; ?>
                <?php if (in_array('Endereco', $camposSelecionados)) echo '<td>' . htmlspecialchars($funcionario['Endereco'] ?? '') . '</td>'; ?>
                <?php if (in_array('Sexo', $camposSelecionados)) echo '<td>' . htmlspecialchars($funcionario['Sexo'] ?? '') . '</td>'; ?>
                <?php if (in_array('Salario', $camposSelecionados)) echo '<td>' . htmlspecialchars($funcionario['Salario'] ?? '') . '</td>'; ?>
                <?php if (in_array('SupervisorID', $camposSelecionados)) echo '<td>' . htmlspecialchars($funcionario['SupervisorID'] ?? '') . '</td>'; ?>
                <?php if (in_array('DepartamentoNumero', $camposSelecionados)) echo '<td>' . htmlspecialchars($funcionario['DepartamentoNumero'] ?? '') . '</td>'; ?>
                <?php if (in_array('ProjetoNumero', $camposSelecionados)) echo '<td>' . htmlspecialchars($funcionario['ProjetoNumero'] ?? '') . '</td>'; ?>
                <td>
                    <!-- Botão Excluir -->
                    <a href="?acao=excluirFuncionario&cpf=<?php echo htmlspecialchars($funcionario['Cpf'] ?? ''); ?>" onclick="return confirm('Deseja realmente excluir este funcionário?')">
                        <button>Excluir</button>
                    </a>

                    <!-- Botão Editar -->
                    <a href="?acao=editarFuncionarioForm&cpf=<?php echo htmlspecialchars($funcionario['Cpf'] ?? ''); ?>">
                        <button>Editar</button>
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Formulário para adicionar funcionário -->
<div class="form-container">
    <h3>Adicionar Funcionário</h3>
    <form method="POST" action="?acao=adicionarFuncionario">
        <label>CPF:</label><br>
        <input type="text" name="cpf" required><br><br>
        <label>Primeiro Nome:</label><br>
        <input type="text" name="pnome" required><br><br>
        <label>Inicial do Meio:</label><br>
        <input type="text" name="minicial" required><br><br>
        <label>Último Nome:</label><br>
        <input type="text" name="unome" required><br><br>
        <label>Data de Nascimento:</label><br>
        <input type="date" name="dataNasc" required><br><br>
        <label>Endereço:</label><br>
        <input type="text" name="endereco" required><br><br>
        <label>Sexo:</label><br>
        <select name="sexo" required>
            <option value="M">Masculino</option>
            <option value="F">Feminino</option>
        </select><br><br>
        <label>Salário:</label><br>
        <input type="text" name="salario" required><br><br>
        <label>CPF do Supervisor:</label><br>
        <input type="text" name="supervisorID"><br><br>
        <label>Número do Departamento:</label><br>
        <input type="text" name="departamentoNumero"><br><br>
        <label>Número do Projeto:</label><br>
        <input type="text" name="projetoNumero"><br><br>
        <button type="submit">Adicionar Funcionário</button>
    </form>
</div>

<!-- Formulário de Edição (será exibido apenas se a ação for editarFuncionarioForm) -->
<?php if ($acao === 'editarFuncionarioForm' && $cpfFuncionario): ?>
    <?php
        // Buscar dados do funcionário para preenchê-los no formulário
        $sql = "SELECT * FROM FUNCIONARIO WHERE Cpf = :cpf";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':cpf', $cpfFuncionario);
        $stmt->execute();
        $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);
    ?>
    <h2>Editar Funcionário</h2>
    <form method="POST" action="?acao=editarFuncionario">
        <input type="hidden" name="cpf" value="<?php echo htmlspecialchars($funcionario['Cpf'] ?? ''); ?>">
        <label for="pnome">Primeiro Nome:</label>
        <input type="text" name="pnome" id="pnome" value="<?php echo htmlspecialchars($funcionario['Pnome'] ?? ''); ?>" required><br>
        <label for="minicial">Inicial do Meio:</label>
        <input type="text" name="minicial" id="minicial" value="<?php echo htmlspecialchars($funcionario['Minicial'] ?? ''); ?>" required><br>
        <label for="unome">Último Nome:</label>
        <input type="text" name="unome" id="unome" value="<?php echo htmlspecialchars($funcionario['Unome'] ?? ''); ?>" required><br>
        <label for="dataNasc">Data de Nascimento:</label>
        <input type="date" name="dataNasc" id="dataNasc" value="<?php echo htmlspecialchars($funcionario['DataNasc'] ?? ''); ?>" required><br>
        <label for="endereco">Endereço:</label>
        <input type="text" name="endereco" id="endereco" value="<?php echo htmlspecialchars($funcionario['Endereco'] ?? ''); ?>" required><br>
        <label for="sexo">Sexo:</label>
        <select name="sexo" id="sexo" required>
            <option value="M" <?php echo $funcionario['Sexo'] == 'M' ? 'selected' : ''; ?>>Masculino</option>
            <option value="F" <?php echo $funcionario['Sexo'] == 'F' ? 'selected' : ''; ?>>Feminino</option>
        </select><br>
        <label for="salario">Salário:</label>
        <input type="number" name="salario" id="salario" value="<?php echo htmlspecialchars($funcionario['Salario'] ?? ''); ?>" required><br>
        <label for="supervisorID">CPF do Supervisor:</label>
        <input type="text" name="supervisorID" id="supervisorID" value="<?php echo htmlspecialchars($funcionario['SupervisorID'] ?? ''); ?>"><br>
        <label for="departamentoNumero">Número do Departamento:</label>
        <input type="text" name="departamentoNumero" id="departamentoNumero" value="<?php echo htmlspecialchars($funcionario['DepartamentoNumero'] ?? ''); ?>"><br>
        <label for="projetoNumero">Número do Projeto:</label>
        <input type="text" name="projetoNumero" id="projetoNumero" value="<?php echo htmlspecialchars($funcionario['ProjetoNumero'] ?? ''); ?>"><br>
        <button type="submit">Salvar</button>
    </form>
<?php endif; ?>


<!-- Listar Dependentes -->
<h2>Dependentes</h2>

<!-- Formulário para selecionar campos -->
<form method="POST" action="">
    <label><input type="checkbox" name="campos[]" value="Nome" <?php echo in_array('Nome', $_POST['campos'] ?? ['Nome', 'Sexo', 'DataNascimento', 'Parentesco', 'FuncionarioCpf']) ? 'checked' : ''; ?>> Nome</label>
    <label><input type="checkbox" name="campos[]" value="Sexo" <?php echo in_array('Sexo', $_POST['campos'] ?? ['Nome', 'Sexo', 'DataNascimento', 'Parentesco', 'FuncionarioCpf']) ? 'checked' : ''; ?>> Sexo</label>
    <label><input type="checkbox" name="campos[]" value="DataNascimento" <?php echo in_array('DataNascimento', $_POST['campos'] ?? ['Nome', 'Sexo', 'DataNascimento', 'Parentesco', 'FuncionarioCpf']) ? 'checked' : ''; ?>> Data de Nascimento</label>
    <label><input type="checkbox" name="campos[]" value="Parentesco" <?php echo in_array('Parentesco', $_POST['campos'] ?? ['Nome', 'Sexo', 'DataNascimento', 'Parentesco', 'FuncionarioCpf']) ? 'checked' : ''; ?>> Parentesco</label>
    <label><input type="checkbox" name="campos[]" value="FuncionarioCpf" <?php echo in_array('FuncionarioCpf', $_POST['campos'] ?? ['Nome', 'Sexo', 'DataNascimento', 'Parentesco', 'FuncionarioCpf']) ? 'checked' : ''; ?>> CPF do Funcionário</label>
    <button type="submit">Atualizar Campos</button>
</form>

<?php
// Processar a seleção de campos
$camposSelecionados = $_POST['campos'] ?? ['Nome', 'Sexo', 'DataNascimento', 'Parentesco', 'FuncionarioCpf'];
?>

<table>
    <thead>
        <tr>
            <?php if (in_array('Nome', $camposSelecionados)) echo '<th>Nome</th>'; ?>
            <?php if (in_array('Sexo', $camposSelecionados)) echo '<th>Sexo</th>'; ?>
            <?php if (in_array('DataNascimento', $camposSelecionados)) echo '<th>Data de Nascimento</th>'; ?>
            <?php if (in_array('Parentesco', $camposSelecionados)) echo '<th>Parentesco</th>'; ?>
            <?php if (in_array('FuncionarioCpf', $camposSelecionados)) echo '<th>CPF do Funcionário</th>'; ?>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach (listarDependentes($pdo) as $dependente): ?>
            <tr>
                <?php if (in_array('Nome', $camposSelecionados)) echo '<td>' . htmlspecialchars($dependente['Nome']) . '</td>'; ?>
                <?php if (in_array('Sexo', $camposSelecionados)) echo '<td>' . htmlspecialchars($dependente['Sexo']) . '</td>'; ?>
                <?php if (in_array('DataNascimento', $camposSelecionados)) echo '<td>' . htmlspecialchars($dependente['DataNascimento']) . '</td>'; ?>
                <?php if (in_array('Parentesco', $camposSelecionados)) echo '<td>' . htmlspecialchars($dependente['Parentesco']) . '</td>'; ?>
                <?php if (in_array('FuncionarioCpf', $camposSelecionados)) echo '<td>' . htmlspecialchars($dependente['FuncionarioCpf']) . '</td>'; ?>
                <td>
                    <!-- Botão Excluir -->
                    <a href="?acao=excluirDependente&id=<?php echo htmlspecialchars($dependente['ID']); ?>" 
                       onclick="return confirm('Deseja realmente excluir este dependente?')">
                        <button>Excluir</button>
                    </a>

                    <!-- Botão Editar -->
                    <a href="?acao=editarDependenteForm&id=<?php echo htmlspecialchars($dependente['ID']); ?>">
                        <button>Editar</button>
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Formulário para adicionar dependente -->
<div class="form-container">
    <h3>Adicionar Dependente</h3>
    <form method="POST" action="?acao=adicionarDependente">
        <label>Nome:</label><br>
        <input type="text" name="nome" required><br><br>
        <label>Sexo:</label><br>
        <select name="sexo" required>
            <option value="M">Masculino</option>
            <option value="F">Feminino</option>
        </select><br><br>
        <label>Data de Nascimento:</label><br>
        <input type="date" name="dataNascimento" required><br><br>
        <label>Parentesco:</label><br>
        <input type="text" name="parentesco" required><br><br>
        <label>CPF do Funcionário:</label><br>
        <input type="text" name="cpfFuncionario" required><br><br>
        <button type="submit">Adicionar Dependente</button>
    </form>
</div>

<!-- Formulário de Edição (será exibido apenas se a ação for editarDependenteForm) -->
<?php if ($acao === 'editarDependenteForm' && isset($_GET['id'])): ?>
    <?php
        $idDependente = $_GET['id'];

        // Buscar dados do dependente para preenchê-los no formulário
        $sql = "SELECT * FROM DEPENDENTE WHERE ID = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $idDependente);
        $stmt->execute();
        $dependente = $stmt->fetch(PDO::FETCH_ASSOC);
    ?>
    <h2>Editar Dependente</h2>
    <form method="POST" action="?acao=editarDependente">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($dependente['ID']); ?>">
        <label for="novoNome">Nome:</label>
        <input type="text" name="novoNome" id="novoNome" value="<?php echo htmlspecialchars($dependente['Nome']); ?>" required><br>
        <label for="sexo">Sexo:</label>
        <select name="sexo" id="sexo" required>
            <option value="M" <?php echo $dependente['Sexo'] == 'M' ? 'selected' : ''; ?>>Masculino</option>
            <option value="F" <?php echo $dependente['Sexo'] == 'F' ? 'selected' : ''; ?>>Feminino</option>
        </select><br>
        <label for="dataNascimento">Data de Nascimento:</label>
        <input type="date" name="dataNascimento" id="dataNascimento" value="<?php echo htmlspecialchars($dependente['DataNascimento']); ?>" required><br>
        <label for="parentesco">Parentesco:</label>
        <input type="text" name="parentesco" id="parentesco" value="<?php echo htmlspecialchars($dependente['Parentesco']); ?>" required><br>
        <button type="submit">Salvar</button>
    </form>
<?php endif; ?>

<?php
// Processamento da adição de dependente
if ($acao === 'adicionarDependente' && isset($_POST['nome'], $_POST['sexo'], $_POST['dataNascimento'], $_POST['parentesco'], $_POST['cpfFuncionario'])) {
    $nome = $_POST['nome'];
    $sexo = $_POST['sexo'];
    $dataNascimento = $_POST['dataNascimento'];
    $parentesco = $_POST['parentesco'];
    $cpfFuncionario = $_POST['cpfFuncionario'];

    // Adiciona o dependente no banco de dados
    $sql = "INSERT INTO DEPENDENTE (Nome, Sexo, DataNascimento, Parentesco, FuncionarioCpf) VALUES (:nome, :sexo, :dataNascimento, :parentesco, :cpfFuncionario)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':sexo', $sexo);
    $stmt->bindParam(':dataNascimento', $dataNascimento);
    $stmt->bindParam(':parentesco', $parentesco);
    $stmt->bindParam(':cpfFuncionario', $cpfFuncionario);

    if ($stmt->execute()) {
        echo "Dependente adicionado com sucesso!";
    } else {
        echo "Erro ao adicionar dependente: " . implode(", ", $stmt->errorInfo());
    }
}

// Processamento da edição do dependente
if ($acao === 'editarDependente' && isset($_POST['id'], $_POST['novoNome'], $_POST['sexo'], $_POST['dataNascimento'], $_POST['parentesco'])) {
    $id = $_POST['id'];
    $novoNome = $_POST['novoNome'];
    $sexo = $_POST['sexo'];
    $dataNascimento = $_POST['dataNascimento'];
    $parentesco = $_POST['parentesco'];

    // Atualiza o dependente no banco de dados
    $sql = "UPDATE DEPENDENTE SET Nome = :novoNome, Sexo = :sexo, DataNascimento = :dataNascimento, Parentesco = :parentesco WHERE ID = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':novoNome', $novoNome);
    $stmt->bindParam(':sexo', $sexo);
    $stmt->bindParam(':dataNascimento', $dataNascimento);
    $stmt->bindParam(':parentesco', $parentesco);
    $stmt->execute();
}
?>


<!-- Listar Departamentos -->
<h2>Departamentos</h2>

<!-- Formulário para selecionar campos -->
<form method="POST" action="">
    <label><input type="checkbox" name="campos[]" value="GerenteCpf" <?php echo in_array('GerenteCpf', $_POST['campos'] ?? ['GerenteCpf', 'Nome', 'Localizacao', 'Numero']) ? 'checked' : ''; ?>> CPF do Gerente</label>
    <label><input type="checkbox" name="campos[]" value="Nome" <?php echo in_array('Nome', $_POST['campos'] ?? ['GerenteCpf', 'Nome', 'Localizacao', 'Numero']) ? 'checked' : ''; ?>> Nome</label>
    <label><input type="checkbox" name="campos[]" value="Localizacao" <?php echo in_array('Localizacao', $_POST['campos'] ?? ['GerenteCpf', 'Nome', 'Localizacao', 'Numero']) ? 'checked' : ''; ?>> Localização</label>
    <label><input type="checkbox" name="campos[]" value="Numero" <?php echo in_array('Numero', $_POST['campos'] ?? ['GerenteCpf', 'Nome', 'Localizacao', 'Numero']) ? 'checked' : ''; ?>> Número</label>
    <button type="submit">Atualizar Campos</button>
</form>

<?php
// Processar a seleção de campos
$camposSelecionados = $_POST['campos'] ?? ['GerenteCpf', 'Nome', 'Localizacao', 'Numero'];
?>

<table>
    <thead>
        <tr>
            <?php if (in_array('GerenteCpf', $camposSelecionados)) echo '<th>CPF do Gerente</th>'; ?>
            <?php if (in_array('Nome', $camposSelecionados)) echo '<th>Nome</th>'; ?>
            <?php if (in_array('Localizacao', $camposSelecionados)) echo '<th>Localização</th>'; ?>
            <?php if (in_array('Numero', $camposSelecionados)) echo '<th>Número</th>'; ?>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach (listarDepartamentos($pdo) as $departamento): ?>
            <tr>
                <?php if (in_array('GerenteCpf', $camposSelecionados)) echo '<td>' . htmlspecialchars($departamento['GerenteCpf'] ?? '') . '</td>'; ?>
                <?php if (in_array('Nome', $camposSelecionados)) echo '<td>' . htmlspecialchars($departamento['Nome'] ?? '') . '</td>'; ?>
                <?php if (in_array('Localizacao', $camposSelecionados)) echo '<td>' . htmlspecialchars($departamento['Localizacao'] ?? '') . '</td>'; ?>
                <?php if (in_array('Numero', $camposSelecionados)) echo '<td>' . htmlspecialchars($departamento['Numero'] ?? '') . '</td>'; ?>
                <td>
                    <!-- Botão Excluir -->
                    <a href="?acao=excluirDepartamento&nome=<?php echo htmlspecialchars($departamento['Nome'] ?? ''); ?>" 
                       onclick="return confirm('Deseja realmente excluir este departamento?')">
                        <button>Excluir</button>
                    </a>

                    <!-- Botão Editar -->
                    <a href="?acao=editarDepartamentoForm&nome=<?php echo htmlspecialchars($departamento['Nome'] ?? ''); ?>">
                        <button>Editar</button>
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Formulário para adicionar departamento -->
<div class="form-container">
    <h3>Adicionar Departamento</h3>
    <form method="POST" action="?acao=adicionarDepartamento">
        <label>CPF do Gerente:</label><br>
        <input type="text" name="gerenteCpf" required><br><br>
        <label>Nome:</label><br>
        <select name="nome_predefinido" id="nome_predefinido" onchange="toggleNomeInput(this.value)">
            <option value="">Selecione um nome</option>
            <option value="Marketing">Marketing</option>
            <option value="Vendas">Vendas</option>
            <option value="TI">TI</option>
            <option value="Financeiro">Financeiro</option>
            <option value="Outro">Outro</option>
        </select><br><br>
        <div id="nomeOutroContainer" style="display: none;">
            <label>Nome do Departamento:</label><br>
            <input type="text" name="nome" id="nome"><br><br>
        </div>
        <label>Localização:</label><br>
        <input type="text" name="localizacao" required><br><br>
        <button type="submit">Adicionar Departamento</button>
    </form>
</div>

<script>
function toggleNomeInput(value) {
    var nomeOutroContainer = document.getElementById('nomeOutroContainer');
    var nomeInput = document.getElementById('nome');
    if (value === 'Outro') {
        nomeOutroContainer.style.display = 'block';
        nomeInput.required = true;
    } else {
        nomeOutroContainer.style.display = 'none';
        nomeInput.required = false;
    }
}
</script>

<?php
if ($acao === 'adicionarDepartamento' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gerenteCpf'], $_POST['localizacao'])) {
    $gerenteCpf = $_POST['gerenteCpf'];
    $nome = $_POST['nome_predefinido'] === 'Outro' ? $_POST['nome'] : $_POST['nome_predefinido'];
    $localizacao = $_POST['localizacao'];

    // Verifica se o CPF do gerente existe na tabela FUNCIONARIO
    $sql = "SELECT COUNT(*) FROM FUNCIONARIO WHERE Cpf = :gerenteCpf";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':gerenteCpf', $gerenteCpf);
    $stmt->execute();
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        echo "Erro: CPF do gerente não encontrado.";
    } else {
        // Adiciona ou atualiza o departamento no banco de dados
        $sql = "INSERT INTO DEPARTAMENTO (Nome, GerenteCpf, Localizacao) VALUES (:nome, :gerenteCpf, :localizacao)
                ON DUPLICATE KEY UPDATE GerenteCpf = :gerenteCpf, Localizacao = :localizacao";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':gerenteCpf', $gerenteCpf);
        $stmt->bindParam(':localizacao', $localizacao);

        if ($stmt->execute()) {
            echo "Departamento adicionado ou atualizado com sucesso!";
        } else {
            echo "Erro ao adicionar ou atualizar departamento: " . implode(", ", $stmt->errorInfo());
        }
    }
}
?>

<!-- Formulário de Edição (será exibido apenas se a ação for editarDepartamentoForm) -->
<?php if ($acao === 'editarDepartamentoForm' && isset($_GET['nome'])): ?>
    <?php
        $nomeDepartamento = $_GET['nome'];        // Buscar dados do departamento para preenchê-los no formulário
        $sql = "SELECT * FROM DEPARTAMENTO WHERE Nome = :nome";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':nome', $nomeDepartamento);
        $stmt->execute();
        $departamento = $stmt->fetch(PDO::FETCH_ASSOC);
    ?>
    <h2>Editar Departamento</h2>
    <form method="POST" action="?acao=editarDepartamento">
        <input type="hidden" name="nomeAtual" value="<?php echo htmlspecialchars($departamento['Nome'] ?? ''); ?>">
        <label for="nome">Nome:</label>
        <select name="nome" id="nome" required>
            <option value="Marketing" <?php echo $departamento['Nome'] == 'Marketing' ? 'selected' : ''; ?>>Marketing</option>
            <option value="Vendas" <?php echo $departamento['Nome'] == 'Vendas' ? 'selected' : ''; ?>>Vendas</option>
            <option value="TI" <?php echo $departamento['Nome'] == 'TI' ? 'selected' : ''; ?>>TI</option>
            <option value="Financeiro" <?php echo $departamento['Nome'] == 'Financeiro' ? 'selected' : ''; ?>>Financeiro</option>
        </select><br>
        <label for="localizacao">Localização:</label>
        <input type="text" name="localizacao" id="localizacao" value="<?php echo htmlspecialchars($departamento['Localizacao'] ?? ''); ?>" required><br>
        <label for="gerenteCpf">CPF do Gerente:</label>
        <input type="text" name="gerenteCpf" id="gerenteCpf" value="<?php echo htmlspecialchars($departamento['GerenteCpf'] ?? ''); ?>" required><br>
        <label for="numero">Número:</label>
        <input type="text" name="numero" id="numero" value="<?php echo htmlspecialchars($departamento['Numero'] ?? ''); ?>" required><br>
        <button type="submit">Salvar</button>
    </form>
<?php endif; ?>

<?php
if ($acao === 'editarDepartamento' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nomeAtual'], $_POST['nome'], $_POST['localizacao'], $_POST['gerenteCpf'], $_POST['numero'])) {
    $nomeAtual = $_POST['nomeAtual'];
    $nome = $_POST['nome'];
    $localizacao = $_POST['localizacao'];
    $gerenteCpf = $_POST['gerenteCpf'];
    $numero = $_POST['numero'];

    // Verifica se o CPF do gerente existe na tabela FUNCIONARIO
    $sql = "SELECT COUNT(*) FROM FUNCIONARIO WHERE Cpf = :gerenteCpf";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':gerenteCpf', $gerenteCpf);
    $stmt->execute();
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        echo "Erro: CPF do gerente não encontrado.";
    } else {
        // Atualiza o departamento no banco de dados
        $sql = "UPDATE DEPARTAMENTO SET Nome = :nome, Localizacao = :localizacao, GerenteCpf = :gerenteCpf, Numero = :numero WHERE Nome = :nomeAtual";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':localizacao', $localizacao);
        $stmt->bindParam(':gerenteCpf', $gerenteCpf);
        $stmt->bindParam(':numero', $numero);
        $stmt->bindParam(':nomeAtual', $nomeAtual);
        $stmt->execute();
    }
}
?>

<!-- Listar Projetos -->
<h2>Projetos</h2>

<!-- Formulário para selecionar campos -->
<form method="POST" action="">
    <label><input type="checkbox" name="campos[]" value="Nome" <?php echo in_array('Nome', $_POST['campos'] ?? ['Nome', 'Localizacao', 'Numero']) ? 'checked' : ''; ?>> Nome</label>
    <label><input type="checkbox" name="campos[]" value="Localizacao" <?php echo in_array('Localizacao', $_POST['campos'] ?? ['Nome', 'Localizacao', 'Numero']) ? 'checked' : ''; ?>> Localização</label>
    <label><input type="checkbox" name="campos[]" value="Numero" <?php echo in_array('Numero', $_POST['campos'] ?? ['Nome', 'Localizacao', 'Numero']) ? 'checked' : ''; ?>> Número</label>
    <button type="submit">Atualizar Campos</button>
</form>

<?php
// Processar a seleção de campos
$camposSelecionados = $_POST['campos'] ?? ['Nome', 'Localizacao', 'Numero'];
?>

<table>
    <thead>
        <tr>
            <?php if (in_array('Nome', $camposSelecionados)) echo '<th>Nome</th>'; ?>
            <?php if (in_array('Localizacao', $camposSelecionados)) echo '<th>Localização</th>'; ?>
            <?php if (in_array('Numero', $camposSelecionados)) echo '<th>Número</th>'; ?>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach (listarProjetos($pdo) as $projeto): ?>
            <tr>
                <?php if (in_array('Nome', $camposSelecionados)) echo '<td>' . htmlspecialchars($projeto['Nome'] ?? '') . '</td>'; ?>
                <?php if (in_array('Localizacao', $camposSelecionados)) echo '<td>' . htmlspecialchars($projeto['Localizacao'] ?? '') . '</td>'; ?>
                <?php if (in_array('Numero', $camposSelecionados)) echo '<td>' . htmlspecialchars($projeto['Numero'] ?? '') . '</td>'; ?>
                <td>
                    <!-- Botão Excluir -->
                    <a href="?acao=excluirProjeto&nome=<?php echo htmlspecialchars($projeto['Nome'] ?? ''); ?>" 
                       onclick="return confirm('Deseja realmente excluir este projeto?')">
                        <button>Excluir</button>
                    </a>

                    <!-- Botão Editar -->
                    <a href="?acao=editarProjetoForm&nome=<?php echo htmlspecialchars($projeto['Nome'] ?? ''); ?>">
                        <button>Editar</button>
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Formulário para adicionar projeto -->
<div class="form-container">
    <h3>Adicionar Projeto</h3>
    <form method="POST" action="?acao=adicionarProjeto">
        <label>Nome:</label><br>
        <select name="nome_predefinido" id="nome_predefinido" onchange="toggleNomeInput2(this.value)">
            <option value="">Selecione um nome</option>
            <option value="Conexão Criativa" data-departamento="Marketing">Conexão Criativa</option>
            <option value="Impulso de Resultados" data-departamento="Vendas">Impulso de Resultados</option>
            <option value="TechNext" data-departamento="TI">TechNext</option>
            <option value="Equilíbrio Financeiro" data-departamento="Financeiro">Equilíbrio Financeiro</option>
            <option value="Outro">Outro</option>
        </select><br><br>
        <div id="nomeOutroContainer2" style="display: none;">
            <label>Nome do Projeto:</label><br>
            <input type="text" name="nome" id="nome"><br><br>
        </div>
        <label>Localização:</label><br>
        <input type="text" name="localizacao" required><br><br>
        <label>Número:</label><br>
        <input type="text" name="numero" required><br><br>
        <label>Departamento:</label><br>
        <select name="departamento" required>
            <?php
            // Buscar departamentos do banco de dados
            $sql = "SELECT Nome FROM DEPARTAMENTO";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $departamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($departamentos as $departamento) {
                echo '<option value="' . htmlspecialchars($departamento['Nome']) . '">' . htmlspecialchars($departamento['Nome']) . '</option>';
            }
            ?>
        </select><br><br>
        <button type="submit">Adicionar Projeto</button>
    </form>
</div>

<script>
function toggleNomeInput2(value) {
    var nomeOutroContainer = document.getElementById('nomeOutroContainer2');
    var nomeInput = document.getElementById('nome');
    if (value === 'Outro') {
        nomeOutroContainer.style.display = 'block';
        nomeInput.required = true;
    } else {
        nomeOutroContainer.style.display = 'none';
        nomeInput.required = false;
    }
}
</script>

<?php
if ($acao === 'adicionarProjeto' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome_predefinido'], $_POST['localizacao'], $_POST['numero'], $_POST['departamento'])) {
    $nome = $_POST['nome_predefinido'] === 'Outro' ? $_POST['nome'] : $_POST['nome_predefinido'];
    $localizacao = $_POST['localizacao'];
    $departamento = $_POST['departamento'];

    if (empty($nome)) {
        echo "Erro: O nome do projeto não pode estar vazio.";
    } else {
        // Verificar se o projeto já existe
        $sql = "SELECT COUNT(*) FROM PROJETO WHERE Nome = :nome";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':nome', $nome);
        $stmt->execute();
        $count = $stmt->fetchColumn();
        echo $count;
        echo $nome;
        if ($count > 1) {
            echo "Erro: Projeto com este nome já existe.";
        } else {
            // Adiciona o projeto no banco de dados
            $sql = "INSERT INTO PROJETO (Nome, Localizacao, Numero) VALUES (:nome, :localizacao, :numero)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':localizacao', $localizacao);

            if ($stmt->execute()) {
                echo "Projeto adicionado com sucesso!";
            } else {
                echo "Erro ao adicionar projeto: " . implode(", ", $stmt->errorInfo());
            }
        }
    }
}
?>

<!-- Formulário de Edição de Projeto -->
<?php if ($acao === 'editarProjetoForm' && isset($_GET['nome'])): ?>
    <?php
        $nomeProjeto = $_GET['nome'];
        // Buscar dados do projeto para preenchê-los no formulário
        $sql = "SELECT P.Nome, P.Localizacao, P.Numero, P.Departamento 
                FROM PROJETO P
                WHERE P.Nome = :nome";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':nome', $nomeProjeto);
        $stmt->execute();
        $projeto = $stmt->fetch(PDO::FETCH_ASSOC);
    ?>
    <h2>Editar Projeto</h2>
    <form method="POST" action="?acao=editarProjeto">
        <input type="hidden" name="nomeAtual" value="<?php echo htmlspecialchars($projeto['Nome'] ?? ''); ?>">
        <label for="nome">Nome:</label>
        <select name="nome_predefinido" id="nome_predefinido" onchange="toggleNomeInput(this.value)" required>
            <option value="Conexão Criativa" <?php echo $projeto['Nome'] == 'Conexão Criativa' ? 'selected' : ''; ?>>Conexão Criativa</option>
            <option value="Impulso de Resultados" <?php echo $projeto['Nome'] == 'Impulso de Resultados' ? 'selected' : ''; ?>>Impulso de Resultados</option>
            <option value="TechNext" <?php echo $projeto['Nome'] == 'TechNext' ? 'selected' : ''; ?>>TechNext</option>
            <option value="Equilíbrio Financeiro" <?php echo $projeto['Nome'] == 'Equilíbrio Financeiro' ? 'selected' : ''; ?>>Equilíbrio Financeiro</option>
            <option value="Outro" <?php echo !in_array($projeto['Nome'], ['Conexão Criativa', 'Impulso de Resultados', 'TechNext', 'Equilíbrio Financeiro']) ? 'selected' : ''; ?>>Outro</option>
        </select><br><br>
        <div id="nomeOutroContainer" style="display: <?php echo !in_array($projeto['Nome'], ['Conexão Criativa', 'Impulso de Resultados', 'TechNext', 'Equilíbrio Financeiro']) ? 'block' : 'none'; ?>;">
            <label>Nome do Projeto:</label><br>
            <input type="text" name="nome" id="nome" value="<?php echo htmlspecialchars($projeto['Nome'] ?? ''); ?>"><br><br>
        </div>
        <label for="localizacao">Localização:</label>
        <input type="text" name="localizacao" id="localizacao" value="<?php echo htmlspecialchars($projeto['Localizacao'] ?? ''); ?>" required><br>
        <label for="numero">Número:</label>
        <input type="text" name="numero" id="numero" value="<?php echo htmlspecialchars($projeto['Numero'] ?? ''); ?>" required><br>
        <label for="departamento">Departamento:</label>
        <select name="departamento" id="departamento" required>
            <?php
            // Buscar departamentos do banco de dados
            $sql = "SELECT Nome FROM DEPARTAMENTO";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $departamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($departamentos as $departamento) {
                echo '<option value="' . htmlspecialchars($departamento['Nome']) . '" ' . ($projeto['Departamento'] == $departamento['Nome'] ? 'selected' : '') . '>' . htmlspecialchars($departamento['Nome']) . '</option>';
            }
            ?>
        </select><br>
        <button type="submit">Salvar</button>
    </form>
<?php endif; ?>

<?php
// Processamento da adição de projeto
if ($acao === 'adicionarProjeto' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome_predefinido'], $_POST['localizacao'])) {
    $nome = $_POST['nome_predefinido'] === 'Outro' ? $_POST['nome'] : $_POST['nome_predefinido'];
    $localizacao = $_POST['localizacao'];

    if (empty($nome)) {
        echo "Erro: O nome do projeto não pode estar vazio.";
    } else {
        // Verificar se o projeto já existe
        $sql = "SELECT COUNT(*) FROM PROJETO WHERE Nome = :nome";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':nome', $nome);
        $stmt->execute();
        $count = $stmt->fetchColumn();

        if ($count > 1) {
            echo "Erro: Projeto com este nome já existe.";
        } else {
            // Adiciona o projeto no banco de dados
            $sql = "INSERT INTO PROJETO (Nome, Localizacao) VALUES (:nome, :localizacao)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':localizacao', $localizacao);

            if ($stmt->execute()) {
                echo "Projeto adicionado com sucesso!";
            } else {
                echo "Erro ao adicionar projeto: " . implode(", ", $stmt->errorInfo());
            }
        }
    }
}

// Processamento da edição do projeto
if ($acao === 'editarProjeto' && isset($_POST['nomeAtual'], $_POST['nome'], $_POST['localizacao'])) {
    $nomeAtual = $_POST['nomeAtual'];
    $nome = $_POST['nome'];
    $localizacao = $_POST['localizacao'];

    if (empty($nome)) {
        echo "Erro: O nome do projeto não pode estar vazio.";
    } else {
        // Verificar se o novo nome do projeto já existe (exceto o próprio projeto sendo editado)
        $sql = "SELECT COUNT(*) FROM PROJETO WHERE Nome = :nome AND Nome != :nomeAtual";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':nomeAtual', $nomeAtual);
        $stmt->execute();
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            echo "Erro: Projeto com este nome já existe.";
        } else {
            // Atualiza o projeto no banco de dados
            $sql = "UPDATE PROJETO SET Nome = :nome, Localizacao = :localizacao WHERE Nome = :nomeAtual";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':localizacao', $localizacao);
            $stmt->bindParam(':nomeAtual', $nomeAtual);
            $stmt->execute();

            // Redirecionar após a atualização
            header("Location: listagem.php"); // Ou a página onde você lista os projetos
            exit;
        }
    }
}
?>

<!-- No arquivo listagem.php -->
<a href="consulta.php">
    <button style="background-color: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
        Ir para Consultas
    </button>
</a>

</body>
</html>