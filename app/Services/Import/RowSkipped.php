<?php

namespace App\Services\Import;

use RuntimeException;

/** Thrown by an importer's handleRow() to skip a row with a reason. */
class RowSkipped extends RuntimeException {}
