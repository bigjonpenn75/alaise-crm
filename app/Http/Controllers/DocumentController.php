<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /** Documents live outside the web root; this is the only way to read one. */
    public function __invoke(Document $document)
    {
        $owner = $document->documentable;
        abort_unless($owner, 404);
        $this->authorize('access-business', $owner->business);
        abort_unless(Storage::disk('local')->exists($document->path), 404, 'The file for this document is missing from storage.');

        $extension = pathinfo($document->path, PATHINFO_EXTENSION);

        return Storage::disk('local')->download($document->path, $document->display_name.($extension ? '.'.$extension : ''));
    }
}
