<?php

require __DIR__ . '/../vendor/autoload.php';

use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfParser\Type\PdfArray;
use setasign\Fpdi\PdfParser\Type\PdfDictionary;
use setasign\Fpdi\PdfParser\Type\PdfName;
use setasign\Fpdi\PdfParser\Type\PdfString;
use setasign\Fpdi\PdfParser\Type\PdfType;
use setasign\Fpdi\PdfParser\Type\PdfNumeric;

function collectFields($parser, $fieldObject, $parent = '')
{
    try {
        $field = PdfDictionary::ensure(PdfType::resolve($fieldObject, $parser));
    } catch (Throwable) {
        return [];
    }

    $values = $field->value;
    $name = $parent;

    if (isset($values['T'])) {
        $resolved = PdfType::resolve($values['T'], $parser);
        if ($resolved instanceof PdfString || $resolved instanceof PdfName) {
            $raw = (string) $resolved->value;
            $name = $parent !== '' ? $parent . '.' . $raw : $raw;
        }
    }

    if (isset($values['Kids'])) {
        $kids = PdfArray::ensure(PdfType::resolve($values['Kids'], $parser))->value;
        $result = [];
        foreach ($kids as $kid) {
            $result = array_merge($result, collectFields($parser, $kid, $name));
        }
        return $result;
    }

    if ($name === '' || !isset($values['Rect'])) {
        return [];
    }

    $rect = PdfArray::ensure(PdfType::resolve($values['Rect'], $parser))->value;
    $coords = [];
    foreach ($rect as $item) {
        $val = PdfType::resolve($item, $parser);
        if ($val instanceof PdfNumeric) {
            $coords[] = (float) $val->value;
        } elseif ($val instanceof PdfString) {
            $coords[] = (float) $val->value;
        }
    }

    if (count($coords) !== 4) {
        return [];
    }

    return [[$name, $coords]];
}

// Try prepared version first, then original
$preparedPath = __DIR__ . '/../storage/app/templates/borrow_request_form_v2.prepared.pdf';
$originalPath = __DIR__ . '/../public/pdf/borrow_request_form_v2.pdf';

$path = is_file($preparedPath) ? $preparedPath : $originalPath;

if (!is_file($path)) {
    echo "Template not found at: $path\n";
    exit(1);
}

try {
    $parser = new PdfParser(StreamReader::createByFile($path));
    $catalog = PdfDictionary::ensure($parser->getCatalog());

    if (!isset($catalog->value['AcroForm'])) {
        echo "No AcroForm found in PDF\n";
        exit(1);
    }

    $acroForm = PdfDictionary::ensure(PdfType::resolve($catalog->value['AcroForm'], $parser));

    if (!isset($acroForm->value['Fields'])) {
        echo "No Fields array found in AcroForm\n";
        exit(1);
    }

    $fields = PdfArray::ensure(PdfType::resolve($acroForm->value['Fields'], $parser))->value;
    $allFields = [];

    foreach ($fields as $ref) {
        $allFields = array_merge($allFields, collectFields($parser, $ref));
    }

    echo "Found " . count($allFields) . " fields:\n\n";

    foreach ($allFields as [$name, $rect]) {
        echo sprintf(
            "'%s' => ['llx' => %.2f, 'lly' => %.2f, 'urx' => %.2f, 'ury' => %.2f],\n",
            $name,
            $rect[0],
            $rect[1],
            $rect[2],
            $rect[3]
        );
    }
} catch (Throwable $e) {
    echo "Error parsing PDF: " . $e->getMessage() . "\n";
    exit(1);
}
