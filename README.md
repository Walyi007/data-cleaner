# 🧹 Outil de Nettoyage de Données - Compatible XAMPP

Un outil web simple mais puissant pour nettoyer des données sales (CSV) directement dans votre navigateur.
Parfait pour préparer vos jeux de données avant analyse dans Excel, Power BI, Python ou SQL.

## ✨ Fonctionnalités
- 📥 Upload de fichiers CSV/TSV ou collage direct de données
- 🧹 Options de nettoyage :
   - Suppression des doublons
   - Suppression des espaces en début/fin
   - Suppression des lignes vides
   - Standardisation des formats de dates (vers YYYY-MM-DD)
   - Suppression des colonnes totalement vides
- 👀 Aperçu avant/après avec mise en évidence
- ⬇️ Téléchargement des données nettoyées en format CSV
- 🎨 Interface responsive utilisant les mêmes couleurs/typos que votre portfolio
- 💾 Optionnel : Historique des opérations via MySQL (requiert configuration DB)

- 📊 Statistiques détaillées du nettoyage (lignes traitées, doublons supprimés, etc.)
- 🌐 API JSON pour intégration avec d'autres services (format=json ou Accept: application/json)

## 🛠️ Installation sous XAMPP (Windows)

### Étape 1 : Placer les fichiers
1. Téléchargez et installez [XAMPP](https://www.apachefriends.org/index.html) si ce n'est pas déjà fait (version PHP 8.1+ recommandée).
2. Copiez le dossier `data-cleaner` dans le répertoire `htdocs` de XAMPP :
   ```
   C:\xampp\htdocs\data-cleaner\
   ```
   Vous devriez voir les fichiers : `index.php`, `style.css`, `schema.sql`, `README.md`.

### Étape 2 : (Optionnel) Configurer la base de données pour l'historique
Si vous souhaitez sauvegarder l'historique de vos opérations de nettoyage :
1. Lancez XAMPP Control Panel → Démarrez **Apache** et **MySQL**.
2. Ouvrez votre navigateur et allez sur : `http://localhost/phpmyadmin/`
3. Cliquez sur **\"Nouveau\"** dans le panneau de gauche.
4. Nom de la base : `data_cleaner_db` (ou tout autre nom que vous préférez).
5. Cliquez sur **\"Créer\"**.
6. Sélectionnez votre nouvelle base, puis cliquez sur l'onglet **\"SQL\"**.
7. Copiez-collez le contenu de `schema.sql` dans la zone de texte.
8. Cliquez sur **\"Exécuter\"**.

### Étape 3 : Configurer la connexion à la base (seulement si historique désiré)
1. Dans le dossier `data-cleaner`, créez un fichier nommé `config.php` avec ce contenu :
   ```php
   <?php
   // data-cleaner/config.php
   $db_host = 'localhost';
   $db_name = 'data_cleaner_db'; // ← À adapter si vous avez choisi un autre nom
   $db_user = 'root';           // ← Par défaut sous XAMPP
   $db_pass = '';               // ← Laissez vide sauf si vous avez modifié le mot de passe MySQL
   $charset = 'utf8mb4';

   try {
       $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=$charset", $db_user, $db_pass, [
           PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
           PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
           PDO::ATTR_EMULATE_PREPARES => false,
       ]);
   } catch (PDOException $e) {
       die("Erreur de connexion à la base de données : " . $e->getMessage());
   }
   ?>
   ```
2. Puis, dans `index.php`, ajoutez cette ligne tout en haut (après `session_start();`) :
   ```php
   require_once 'config.php';
   ```
3. Enfin, ajoutez cette fonction quelque part dans le fichier (avant le HTML) pour enregistrer l'historique :
   ```php
   function logCleaningOperation($pdo, $stats) {
       global $pdo; // Si utilisée dans une fonction
       $stmt = $pdo->prepare("INSERT INTO cleaning_history (operation_name, rows_processed, duplicates_removed, empty_rows_removed, columns_removed, dates_standardized, user_ip) 
                             VALUES (?, ?, ?, ?, ?, ?, ?)");
       $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
       $stmt->execute([
           'Nettoyage de données via interface web',
           $stats['rows_processed'] ?? 0,
           $stats['duplicates_removed'] ?? 0,
           $stats['empty_rows_removed'] ?? 0,
           $stats['columns_removed'] ?? 0,
           $stats['dates_standardized'] ?? 0,
           $ip
       ]);
   }
   ```
   Et appelez-la après un nettoyage réussi avec :
   ```php
   logCleaningOperation($pdo, [
       'rows_processed' => count($cleanedData),
       'duplicates_removed' => count($originalData) - count(array_unique(array_map("serialize", $cleanedData))),
       'empty_rows_removed' => count($originalData) - count(array_filter($originalData, function($row) { return !empty(array_filter($row, function($cell){ return !is_null($cell) && $cell !== ''; })); })),
       // ... (les autres stats peuvent être calculées similaires)
   ]);
   ```

### Étape 4 : Tester l'application
1. Lancez le **XAMPP Control Panel**.
2. Assurez-vous que **Apache** est démarré (MySQL seulement nécessaire si vous utilisez l'historique).
3. Ouvrez votre navigateur et allez sur : 
   **`http://localhost/data-cleaner/`**
4. Vous devriez voir l'interface de l'outil de nettoyage de données.
5. Testez avec :
   - Données d'exemple à coller : 
     ```
     Nom,Âge,Date d'inscription
     Jean Dupont, 25 , 12/05/2023
     Marie Martin,30,2023-06-15
     Jean Dupont, 25 , 12/05/2023
     , ,
     Sophie Laurent, 28 , 15.07.2023
     ```
   - Cochez les options : "Supprimer les doublons", "Supprimer les espaces en début/fin", "Supprimer les lignes vides", "Standardiser les formats de dates"
   - Cliquez sur "Nettoyer les données"
   - Vérifiez l'aperçu avant/après
   - Téléchargez le résultat

## 📝 Notes importantes
- **Sécurité** : Cet outil est conçu pour un usage local/de développement. Ne le déployez pas tel quel sur un serveur public sans ajouter une authentification et des vérifications d'entrée plus poussées.
- **Performance** : Pour de très gros fichiers (>100 000 lignes), considérez un traitement en arrière-plan ou une limite de taille d'upload.
- **Extensions possibles** :
   - Ajouter un système de sauvegarde de "recettes" de nettoyage (préférences enregistrées)
   - Permettre l'export en Excel (.xlsx) via la bibliothèque PhpSpreadsheet
   - Ajouter des statistiques détaillées (pourcentages de lignes supprimées, etc.)
   - Intégrer la visualisation basique avec Chart.js directement dans l'aperçu

## 💡 Pourquoi cet outil pour votre profil ?
En tant que **Data Analyst**, vous passez probablement beaucoup de temps à préparer des données. Cet outil démontre votre capacité à :
- Transformer une compétence métier (nettoyage de données) en solution web réutilisable
- Comprendre les flux de données entrants/sortants (upload → traitement → download)
- Penser à l'expérience utilisateur (aperçu instantané, téléprocargement simple)
- Maîtriser le PHP core pour le traitement de données (sans framework)
- Créer un outil immédiatement utile dans votre travail quotidien

Une fois que vous maîtriserez ce projet, vous pourrez facilement l'étendre vers :
- Un outil de profiling de données (comme pandas-profiling mais en PHP)
- Une interface simple pour exécuter des requêtes SQL sur vos fichiers CSV
- Un petit ETL léger pour déplacer des données entre formats

Bon nettoyage de données ! 🧼✨

---

## ▶️ Prochaine étape pour toi
1. **Télécharge et installe XAMPP** si ce n'est pas déjà fait (lien dans le README).
2. **Crée le dossier** `C:\xampp\htdocs\data-cleaner\`.
3. **Copie-colle** chacun des 4 fichiers ci-dessus dans ce dossier avec leurs noms exacts.
4. **Lance XAMPP** → Démarre Apache.
5. **Ouvre ton navigateur** sur : `http://localhost/data-cleaner/`
6. **Teste avec des données sales** pour voir l'outil en action !

---

## 🔮 Idées d'évolution future
Une fois cet outil maîtrisé, tu pourrais :
1. **Ajouter l'historique MySQL** (déjà préparé dans le README) pour suivre tes opérations de nettoyage au fil du temps.
2. **Intégrer ce nettoyeur dans ton portfolio** comme projet "Dev" concret : 
   *« Outil de nettoyage de données web - Permet aux analystes de préparer rapidement des jeux de données sales pour l'analyse »*
3. **Créer une version "API"** qui accepte des données JSON en POST et retourne du JSON nettoyé (parfait pour chaîner avec d'autres services).
4. **Ajouter des conseils contextuels** : « J'ai détecté que 30% de vos dates sont au format DD/MM/YYYY - voulez-vous les convertir ? »

Tu as maintenant un projet tangible qui montre clairement tes compétences **data** (compréhension des problèmes de qualité des données) et **dev** (implémentation web complète, réfléchi, testable). C'est exactement le genre de réalisation qui fait la différence dans un entretien pour un poste hybride Data/Dev.

Si tu rencontres le moindre souci lors de l'installation ou du test, ou si tu veux passer à l'étape suivante (ajout de l'historique MySQL, amélioration de l'interface, etc.), dis-le-moi ! Je suis là pour t'aider à chaque étape. 💪