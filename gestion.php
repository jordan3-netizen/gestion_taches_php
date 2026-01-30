<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestion des Tâches</title>

    <!-- ✅ Bootstrap local (chez toi : css/bootstrap.css) -->
    <link rel="stylesheet" href="css/bootstrap.css">
</head>
<?php
// gestion.php
// Stockage JSON dans tache.json (même dossier)
$file = __DIR__ . "/tache.json";

// Créer le fichier s'il n'existe pas
if (!file_exists($file)) {
    file_put_contents($file, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function lireTaches(string $file): array {
    $content = @file_get_contents($file);
    if ($content === false || trim($content) === "") return [];
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

function ecrireTaches(string $file, array $taches): void {
    file_put_contents($file, json_encode($taches, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function nettoyer(string $s): string {
    return trim($s);
}

$taches = lireTaches($file);

// ----------------- ACTIONS (ADD / EDIT / DELETE) -----------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    // Statuts autorisés
    $statutsAutorises = ["En cours", "Terminée", "En attente"];

    if ($action === "add") {
        $titre = nettoyer($_POST["titre"] ?? "");
        $description = nettoyer($_POST["description"] ?? "");
        $statut = $_POST["statut"] ?? "En cours";

        if ($titre !== "" && $description !== "" && in_array($statut, $statutsAutorises, true)) {
            $maxId = 0;
            foreach ($taches as $t) {
                $maxId = max($maxId, (int)($t["id"] ?? 0));
            }
            $newId = $maxId + 1;

            $taches[] = [
                "id" => $newId,
                "titre" => $titre,
                "description" => $description,
                "statut" => $statut
            ];

            ecrireTaches($file, $taches);
        }

        header("Location: gestion.php");
        exit;
    }

    if ($action === "delete") {
        $id = (int)($_POST["id"] ?? 0);

        $taches = array_values(array_filter($taches, fn($t) => (int)($t["id"] ?? 0) !== $id));
        ecrireTaches($file, $taches);

        header("Location: gestion.php");
        exit;
    }

    if ($action === "edit") {
        $id = (int)($_POST["id"] ?? 0);
        $titre = nettoyer($_POST["titre"] ?? "");
        $description = nettoyer($_POST["description"] ?? "");
        $statut = $_POST["statut"] ?? "En cours";

        if ($id > 0 && $titre !== "" && $description !== "" && in_array($statut, $statutsAutorises, true)) {
            foreach ($taches as &$t) {
                if ((int)($t["id"] ?? 0) === $id) {
                    $t["titre"] = $titre;
                    $t["description"] = $description;
                    $t["statut"] = $statut;
                    break;
                }
            }
            unset($t);

            ecrireTaches($file, $taches);
        }

        header("Location: gestion.php");
        exit;
    }
}

// ----------------- MODE ÉDITION (GET ?edit=id) -----------------
$editId = isset($_GET["edit"]) ? (int)$_GET["edit"] : 0;
$tacheAEditer = null;

if ($editId > 0) {
    foreach ($taches as $t) {
        if ((int)($t["id"] ?? 0) === $editId) {
            $tacheAEditer = $t;
            break;
        }
    }
}

// Statut actuel pour le select
$statutActuel = $tacheAEditer["statut"] ?? "En cours";
?>
<body class="bg-light">
<div class="container py-4">

    <h1 class="text-center fw-bold mb-4">Gestion des Tâches</h1>

    <!-- Formulaire -->
    <div class="card shadow-sm mx-auto" style="max-width: 720px;">
        <div class="card-header bg-primary text-white fw-semibold">
            <?php echo $tacheAEditer ? "Modifier une tâche" : "Ajouter une tâche"; ?>
        </div>

        <div class="card-body">
            <form method="POST" action="gestion.php">
                <input type="hidden" name="action" value="<?php echo $tacheAEditer ? "edit" : "add"; ?>">
                <?php if ($tacheAEditer): ?>
                    <input type="hidden" name="id" value="<?php echo (int)$tacheAEditer["id"]; ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Titre</label>
                    <input class="form-control" type="text" name="titre" required
                           value="<?php echo htmlspecialchars($tacheAEditer["titre"] ?? "", ENT_QUOTES); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="4" required><?php
                        echo htmlspecialchars($tacheAEditer["description"] ?? "", ENT_QUOTES);
                        ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Statut</label>
                    <select class="form-select" name="statut">
                        <option value="En cours"   <?php echo ($statutActuel === "En cours") ? "selected" : ""; ?>>En cours</option>
                        <option value="Terminée"   <?php echo ($statutActuel === "Terminée") ? "selected" : ""; ?>>Terminée</option>
                        <option value="En attente" <?php echo ($statutActuel === "En attente") ? "selected" : ""; ?>>En attente</option>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-success" type="submit">
                        <?php echo $tacheAEditer ? "Enregistrer la modification" : "Ajouter la tâche"; ?>
                    </button>

                    <?php if ($tacheAEditer): ?>
                        <a class="btn btn-outline-secondary" href="gestion.php">Annuler</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des tâches -->
    <h2 class="mt-5 mb-3 fw-bold">Liste des tâches</h2>

    <?php if (count($taches) === 0): ?>
        <div class="alert alert-info">Aucune tâche pour le moment.</div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($taches as $t): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title fw-semibold">
                                <?php echo htmlspecialchars($t["titre"] ?? "", ENT_QUOTES); ?>
                            </h5>
                            <p class="card-text">
                                <?php echo nl2br(htmlspecialchars($t["description"] ?? "", ENT_QUOTES)); ?>
                            </p>

                            <?php
                            $st = $t["statut"] ?? "En cours";
                            if ($st === "Terminée"): ?>
                                <span class="badge bg-success">Terminée</span>
                            <?php elseif ($st === "En cours"): ?>
                                <span class="badge bg-warning text-dark">En cours</span>
                            <?php elseif ($st === "En attente"): ?>
                                <!-- ✅ Trait jaune comme sur ta maquette -->
                                <div style="
                                    width: 18px;
                                    height: 4px;
                                    background-color: #f1c40f;
                                    border-radius: 2px;
                                    margin-top: 6px;
                                "></div>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inconnu</span>
                            <?php endif; ?>
                        </div>

                        <div class="card-footer bg-white border-0 d-flex gap-2 pb-3 px-3">
                            <a class="btn btn-primary btn-sm"
                               href="gestion.php?edit=<?php echo (int)($t["id"] ?? 0); ?>">
                                Modifier
                            </a>

                            <form method="POST" action="gestion.php"
                                  onsubmit="return confirm('Supprimer cette tâche ?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int)($t["id"] ?? 0); ?>">
                                <button class="btn btn-danger btn-sm" type="submit">Supprimer</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>
</body>
</html>
