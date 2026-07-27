<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstitutionalAssetDocument extends Model
{
    protected $fillable = ['institutional_asset_id', 'title', 'document_type', 'path', 'original_name', 'uploaded_by'];
}
