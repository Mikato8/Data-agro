<?php

declare(strict_types=1);

namespace App\Core;

final class SqlSplitter
{
    /**
     * Divide un script SQL en sentencias, respetando cadenas, comentarios y
     * bloques dollar-quoted ($$ ... $$) usados por funciones de PostgreSQL.
     *
     * @return array<int, string>
     */
    public static function split(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $length = strlen($sql);
        $index = 0;
        $inSingle = false;
        $inDouble = false;
        $inLineComment = false;
        $inBlockComment = false;
        $dollarTag = null;

        while ($index < $length) {
            $char = $sql[$index];
            $next = $index + 1 < $length ? $sql[$index + 1] : '';

            if ($inLineComment) {
                $buffer .= $char;
                if ($char === "\n") {
                    $inLineComment = false;
                }
                $index++;
                continue;
            }

            if ($inBlockComment) {
                $buffer .= $char;
                if ($char === '*' && $next === '/') {
                    $buffer .= '/';
                    $index += 2;
                    $inBlockComment = false;
                    continue;
                }
                $index++;
                continue;
            }

            if ($dollarTag !== null) {
                if (substr($sql, $index, strlen($dollarTag)) === $dollarTag) {
                    $buffer .= $dollarTag;
                    $index += strlen($dollarTag);
                    $dollarTag = null;
                    continue;
                }
                $buffer .= $char;
                $index++;
                continue;
            }

            if ($inSingle) {
                $buffer .= $char;
                if ($char === "'" && $next === "'") {
                    $buffer .= "'";
                    $index += 2;
                    continue;
                }
                if ($char === "'") {
                    $inSingle = false;
                }
                $index++;
                continue;
            }

            if ($inDouble) {
                $buffer .= $char;
                if ($char === '"' && $next === '"') {
                    $buffer .= '"';
                    $index += 2;
                    continue;
                }
                if ($char === '"') {
                    $inDouble = false;
                }
                $index++;
                continue;
            }

            if ($char === '-' && $next === '-') {
                $inLineComment = true;
                $buffer .= '--';
                $index += 2;
                continue;
            }

            if ($char === '/' && $next === '*') {
                $inBlockComment = true;
                $buffer .= '/*';
                $index += 2;
                continue;
            }

            if ($char === "'") {
                $inSingle = true;
                $buffer .= $char;
                $index++;
                continue;
            }

            if ($char === '"') {
                $inDouble = true;
                $buffer .= $char;
                $index++;
                continue;
            }

            if ($char === '$') {
                if (preg_match('/^\$[A-Za-z_][A-Za-z0-9_]*\$|^\$\$/', substr($sql, $index), $matches)) {
                    $dollarTag = $matches[0];
                    $buffer .= $dollarTag;
                    $index += strlen($dollarTag);
                    continue;
                }
            }

            if ($char === ';') {
                $statement = trim($buffer);
                if ($statement !== '') {
                    $statements[] = $statement;
                }
                $buffer = '';
                $index++;
                continue;
            }

            $buffer .= $char;
            $index++;
        }

        $statement = trim($buffer);
        if ($statement !== '') {
            $statements[] = $statement;
        }

        return $statements;
    }
}
