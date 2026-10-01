<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Una consulta para anunciar en Tinku, desde el mediakit. */
#[Fillable(['name', 'company', 'email', 'phone', 'formats', 'budget', 'message', 'status', 'handled_by', 'ip'])]
class AdvertiserInquiry extends Model
{
    use Auditable;

    /** @var array<string, string> Formatos que se ofrecen, con su nombre. */
    public const FORMATS = [
        'presentada' => 'Colección presentada por tu marca',
        'espacio' => 'Espacio patrocinado en el inicio y la búsqueda',
        'contenido' => 'Experiencias creadas con tu marca',
        'turismo' => 'Alianza de turismo',
        'beneficios' => 'Beneficios para la comunidad',
    ];

    /** @var array<string, string> */
    public const BUDGETS = [
        'chico' => 'Hasta $ 500.000 por mes',
        'medio' => 'De $ 500.000 a $ 2.000.000 por mes',
        'grande' => 'Más de $ 2.000.000 por mes',
        'definir' => 'Todavía no lo sé',
    ];

    /** @var array<string, string> */
    public const STATUSES = ['new' => 'Nueva', 'contacted' => 'Contactada', 'closed' => 'Cerrada'];

    /** @var list<string> El teléfono no va a la auditoría. */
    protected array $auditMasked = ['phone'];

    protected function casts(): array
    {
        return ['phone' => 'encrypted', 'formats' => 'array'];
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
