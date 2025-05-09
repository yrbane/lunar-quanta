<?php
/**
 *
 * @since 0.0.1
 * @link https://nethttp.net
 * @Author seb@nethttp.net
 *
 *
 */
declare(strict_types=1);

namespace App\Service\Command;

/**
 * Class TableRenderer.
 *
 * Gère l'affichage de tableaux en ASCII avec un alignement précis des colonnes.
 * La colorisation est déléguée à ConsoleHelper.
 */
class TableRenderer
{
    /**
     * Affiche un tableau ASCII simple avec alignement des colonnes.
     *
     * Les options disponibles :
     * - 'columns' (array)      : Tableau associatif définissant les colonnes (clé = index dans les données, valeur = étiquette).
     * - 'borderColor' (string) : Code ANSI pour la bordure (défaut '35').
     * - 'headerColor' (string) : Code ANSI pour l'en-tête (défaut '1;35').
     * - 'rowColor' (string)    : Code ANSI pour les lignes de données (défaut '0;37').
     * - 'showHeaders' (bool)   : Indique si l'en-tête doit être affiché (défaut true).
     *
     * @param array<int, array<string, string>> $rows    liste des lignes de données
     * @param array<string, mixed>              $options options d'affichage
     */
    public static function renderSingleTable(array $rows, array $options = []): void
    {
        // Récupération des options d'affichage
        $columns = $options['columns'] ?? [];
        $borderColor = (string) ($options['borderColor'] ?? '35');
        $headerColor = (string) ($options['headerColor'] ?? '1;35');
        $rowColor = (string) ($options['rowColor'] ?? '0;37');
        $showHeaders = (bool) ($options['showHeaders'] ?? true);

        // Si aucune colonne n'est définie, déduction à partir de la première ligne de données
        if (empty($columns) && !empty($rows)) {
            $keys = array_keys($rows[0]);
            $columns = array_combine($keys, $keys);
        }

        // Calcul de la largeur maximale de chaque colonne
        $encoding = ConsoleHelper::getTerminalEncoding();
        $maxWidths = [];
        foreach ($columns as $colKey => $label) {
            $maxWidths[$colKey] = mb_strwidth($label, $encoding);
        }
        foreach ($rows as $row) {
            foreach ($columns as $colKey => $_label) {
                $cellValue = $row[$colKey] ?? '';
                $cellWidth = mb_strwidth($cellValue, $encoding);
                if ($cellWidth > $maxWidths[$colKey]) {
                    $maxWidths[$colKey] = $cellWidth;
                }
            }
        }

        // Construction de la ligne de bordure horizontale
        $borderLine = '+';
        foreach ($columns as $colKey => $_label) {
            $borderLine .= str_repeat('-', $maxWidths[$colKey] + 2).'+';
        }
        $borderLine .= "\n";
        $borderLine = ConsoleHelper::color($borderLine, $borderColor);

        // Affichage du tableau
        if ($showHeaders && !empty($columns)) {
            echo $borderLine;
            // Construction et affichage de l'en-tête
            $headerLabels = array_values($columns);
            echo self::formatRow(array_keys($columns), $headerLabels, $maxWidths, $headerColor);
            echo $borderLine;
        } else {
            echo $borderLine;
        }

        // Affichage des lignes de données
        foreach ($rows as $row) {
            $rowValues = [];
            foreach ($columns as $colKey => $_label) {
                $rowValues[] = $row[$colKey] ?? '';
            }
            echo self::formatRow(array_keys($columns), $rowValues, $maxWidths, $rowColor);
        }

        // Affichage de la ligne finale
        echo $borderLine;
    }

    /**
     * Affiche un tableau groupé en fonction d'un regroupement défini par clé.
     *
     * Si le tableau $groupedData n'est pas associatif, il sera traité comme un tableau simple.
     *
     * Exemple de structure groupée :
     * <code>
     * [
     *     'USER' => [
     *         ['Commande' => 'user:create', 'Description' => 'Créer un utilisateur'],
     *         ['Commande' => 'user:delete', 'Description' => 'Supprimer un utilisateur']
     *     ],
     *     'CACHE' => [
     *         ['Commande' => 'cache:clear', 'Description' => 'Vider le cache']
     *     ]
     * ]
     * </code>
     *
     * Options d'affichage :
     * - 'borderColor'     (string) : Code ANSI pour la bordure (défaut '35').
     * - 'headerColor'     (string) : Code ANSI pour l'en-tête (défaut '1;35').
     * - 'rowColor'        (string) : Code ANSI pour les données (défaut '0;37').
     * - 'groupLabelColor' (string) : Code ANSI pour le label de groupe (défaut '1;34').
     * - 'showHeaders'     (bool)   : Indique si l'en-tête doit être affiché (défaut true).
     *
     * @param array<int, array<string, string>>|array<int|string, array<int, array<string, string>>> $groupedData données groupées
     * @param array<string, mixed>                                                                   $options     options d'affichage
     */
    public static function renderGrouped(array $groupedData, array $options = []): void
    {
        // Détermination du format (groupé ou simple)
        $isGrouped = self::isAssociative($groupedData);
        if (!$isGrouped) {
            $groupedData = ['' => $groupedData];
        }

        // Agrégation de toutes les lignes pour déterminer l'ensemble des colonnes utilisées
        $allRows = [];
        foreach ($groupedData as $rows) {
            foreach ($rows as $row) {
                $allRows[] = $row;
            }
        }

        // Déduction des colonnes existantes
        $columns = [];
        foreach ($allRows as $row) {
            foreach (array_keys($row) as $colName) {
                if (!in_array($colName, $columns, true)) {
                    $columns[] = $colName;
                }
            }
        }

        // Calcul des largeurs maximales par colonne
        $encoding = ConsoleHelper::getTerminalEncoding();
        $maxWidths = array_fill_keys($columns, 0);
        foreach ($allRows as $row) {
            foreach ($columns as $col) {
                $value = $row[$col] ?? '';
                $colWidth = \mb_strwidth((string) $value, $encoding);
                if ($colWidth > $maxWidths[$col]) {
                    $maxWidths[$col] = $colWidth;
                }
            }
        }

        // Récupération des options d'affichage pour les groupes
        $borderColor = (string) ($options['borderColor'] ?? '35');
        $headerColor = (string) ($options['headerColor'] ?? '1;35');
        $rowColor = (string) ($options['rowColor'] ?? '0;37');
        $groupLabelColor = (string) ($options['groupLabelColor'] ?? '1;34');
        $showHeaders = (bool) ($options['showHeaders'] ?? true);

        // Construction de la ligne horizontale de séparation
        $horizontalLine = '+';
        foreach ($columns as $col) {
            $horizontalLine .= str_repeat('-', $maxWidths[$col] + 2).'+';
        }
        $horizontalLine .= "\n";
        $horizontalLine = ConsoleHelper::color($horizontalLine, $borderColor);

        // Affichage des groupes
        foreach ($groupedData as $group => $rows) {
            if ('' !== $group) {
                $groupDisplay = ConsoleHelper::color("[{$group}]", $groupLabelColor);
                echo "\n{$groupDisplay}\n";
            }
            if ($showHeaders && !empty($rows)) {
                echo $horizontalLine;
                // Affichage de l'en-tête
                echo self::formatRow($columns, $columns, $maxWidths, $headerColor);
                echo $horizontalLine;
            }
            foreach ($rows as $row) {
                $renderValues = [];
                foreach ($columns as $col) {
                    $renderValues[] = $row[$col] ?? '';
                }
                echo self::formatRow($columns, $renderValues, $maxWidths, $rowColor);
            }
            if (!empty($rows)) {
                echo $horizontalLine;
            }
        }
    }

    /**
     * Détermine si un tableau est associatif.
     *
     * Vérifie si les clés du tableau ne correspondent pas à une séquence numérique incrémentale.
     *
     * @param array<mixed> $array le tableau à tester
     *
     * @return bool retourne true si le tableau est associatif, false sinon
     */
    protected static function isAssociative(array $array): bool
    {
        if ([] === $array) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Formate une ligne du tableau en alignant chaque cellule selon la largeur maximale.
     *
     * @param string[]           $orderedColumns tableau ordonné des noms de colonnes
     * @param string[]           $rowValues      valeurs à afficher dans la ligne, dans le même ordre que $orderedColumns
     * @param array<string, int> $maxWidths      tableau associatif indiquant la largeur maximale de chaque colonne
     * @param string             $colorCode      code ANSI pour la coloration de la ligne
     *
     * @return string la ligne formatée suivie d'un saut de ligne
     */
    private static function formatRow(
        array $orderedColumns,
        array $rowValues,
        array $maxWidths,
        string $colorCode
    ): string {
        $encoding = ConsoleHelper::getTerminalEncoding();
        $line = ConsoleHelper::color('| ', $colorCode);
        $columnsCount = count($orderedColumns);

        // Construction de la ligne cellule par cellule
        foreach ($orderedColumns as $index => $colName) {
            $value = (string) ($rowValues[$index] ?? '');
            $padding = $maxWidths[$colName] - \mb_strwidth($value, $encoding);
            $valuePadded = $value.str_repeat(' ', $padding);
            $line .= ConsoleHelper::color($valuePadded, $colorCode);
            $separator = $index < $columnsCount - 1 ? ' | ' : ' |';
            $line .= ConsoleHelper::color($separator, $colorCode);
        }

        return $line."\n";
    }
}
