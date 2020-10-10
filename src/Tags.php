<?php

declare(strict_types=1);

namespace SOFe\Hierardoc;

use const PHP_INT_MAX;
use function array_slice;
use function count;
use function explode;
use function min;
use function strlen;
use function strpos;
use function substr;
use function trim;
use InvalidArgumentException;

/**
 * Represents the tags found in a PHPDoc.
 *
 * The PHPDoc can be a one-liner `\/\*\*` `(?<entry>.*)` `\*\/`,
 * or take multiple lines `\/\*\*\r?\n` `([ \t]*` `(\*[ \t]*)?` `(?<entry>.*)` `\r?\n)*` `\*\/`.
 *
 * Each `<entry>` must follow the syntax `@(?<name>[^ \t]+)` `([ \t]+` `(?<value>.*)` `)?`.
 *
 * Each `<name>` is a fully-qualified path of the tag name, in which components are delimited by `-`.
 * Conventionally, each component follows `camelCase`.
 * For example, `foo-barQux-corge` is the attribute `corge` under the subgroup `barQux` under the group `foo`.
 *
 * Multi-line tags are currently not supported.
 */
final class Tags {
    static public function parse(string $doc) : Tags {
        if(substr($doc, 0, 3) !== "/**" || substr($doc, -2) !== "*/") {
            throw new InvalidArgumentException("The string is not a PHPDoc");
        }

        $doc = substr($doc, 3, -2);
        $lines = explode("\n", $doc);

        $ret = new Tags;
        foreach($lines as $line) {
            $line = trim($line, " \r\t");
            if(strlen($line) === 0) {
                continue;
            }
            if($line[0] === "*") {
                $line = trim(substr($line, 1), " \t");
            }
            if(strlen($line) === 0 || $line[0] !== "@") {
                continue;
            }
            $line = substr($line, 1);

            $space = strpos($line, " ");
            $tab = strpos($line, "\t");
            if($space === false) $space = PHP_INT_MAX;
            if($tab === false) $tab = PHP_INT_MAX;

            $keyPos = min($space, $tab);
            if($keyPos === PHP_INT_MAX) {
                $key = $line;
                $value = "";
            } else {
                $key = substr($line, 0, $keyPos);
                $value = trim(substr($line, $keyPos), " \t");
            }

            $keyComps = explode("-", $key);
            $tags = $ret;
            foreach(array_slice($keyComps, 0, -1) as $comp) {
                if(!isset($tags->groups[$comp])) {
                    $tags->groups[$comp] = new Tags;
                }
                $tags = $tags->groups[$comp];
            }
            $tags->values[$keyComps[count($keyComps) - 1]] = $value;
        }

        return $ret;
    }

    /** @var array<string, Tags> */
    private $groups = [];
    /** @var array<string, string> */
    private $values = [];

    public function getGroup(string $name) : Tags {
        if(strpos($name, "-") !== false) {
            throw new InvalidArgumentException("getGroup does not support nested names");
        }

        if(!isset($this->groups[$name])) {
            $this->groups[$name] = new Tags;
        }

        return $this->groups[$name];
    }

    /**
     * @return array<string, Tags>
     */
    public function getGroups() : array {
        return $this->groups;
    }

    public function hasValue(string $name) : bool {
        if(strpos($name, "-") !== false) {
            throw new InvalidArgumentException("hasValue does not support nested names");
        }

        return isset($this->values[$name]);
    }

    public function getValue(string $name) : ?string {
        if(strpos($name, "-") !== false) {
            throw new InvalidArgumentException("getValue does not support nested names");
        }

        return $this->values[$name] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public function getValues() : array {
        return $this->values;
    }
}
