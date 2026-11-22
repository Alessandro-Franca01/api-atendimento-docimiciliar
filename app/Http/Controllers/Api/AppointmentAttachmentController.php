<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Anexos (fotos) de um atendimento, guardados em storage S3-compatível (MinIO).
 *
 * O bucket é privado: o navegador nunca fala direto com o storage. A leitura
 * passa pela API (download), que valida autenticação e propriedade do atendimento.
 */
class AppointmentAttachmentController extends Controller
{
    private const MAX_FILES_PER_REQUEST = 10;

    private function disk(): string
    {
        return config('filesystems.attachments_disk', 's3');
    }

    private function authorizeAppointment(Request $request, Appointment $appointment): void
    {
        abort_if($appointment->user_id !== $request->user()->id, 403, 'Não autorizado');
    }

    private function authorizeAttachment(Request $request, AppointmentAttachment $attachment): void
    {
        abort_if($attachment->user_id !== $request->user()->id, 403, 'Não autorizado');
    }

    private function present(AppointmentAttachment $a): array
    {
        return [
            'id' => $a->id,
            'appointment_id' => $a->appointment_id,
            'original_name' => $a->original_name,
            'mime_type' => $a->mime_type,
            'size' => $a->size,
            'url' => url("/api/attachments/{$a->id}/download"),
            'created_at' => $a->created_at,
        ];
    }

    public function index(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorizeAppointment($request, $appointment);

        return response()->json(
            AppointmentAttachment::where('appointment_id', $appointment->id)
                ->orderBy('id')
                ->get()
                ->map(fn ($a) => $this->present($a))
        );
    }

    public function store(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorizeAppointment($request, $appointment);

        $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:' . self::MAX_FILES_PER_REQUEST],
            // mimetypes valida pelo conteúdo real do arquivo, não pela extensão
            'files.*' => ['file', 'max:10240', 'mimetypes:image/jpeg,image/png,image/gif,image/webp'],
        ], [
            'files.*.max' => 'Cada imagem deve ter no máximo 10MB.',
            'files.*.mimetypes' => 'Apenas imagens PNG, JPG, GIF ou WEBP são permitidas.',
        ]);

        $disk = $this->disk();
        $saved = [];

        foreach ($request->file('files') as $file) {
            // Nome gerado: o nome original do cliente nunca vira caminho no storage
            $path = "appointments/{$appointment->id}/" . Str::uuid() . '.' . $file->extension();

            Storage::disk($disk)->putFileAs(
                dirname($path),
                $file,
                basename($path),
                ['visibility' => 'private', 'ContentType' => $file->getMimeType()]
            );

            $saved[] = AppointmentAttachment::create([
                'appointment_id' => $appointment->id,
                'user_id' => $request->user()->id,
                'disk' => $disk,
                'path' => $path,
                'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        return response()->json(array_map(fn ($a) => $this->present($a), $saved), 201);
    }

    public function download(Request $request, AppointmentAttachment $attachment): StreamedResponse
    {
        $this->authorizeAttachment($request, $attachment);

        $storage = Storage::disk($attachment->disk);
        abort_unless($storage->exists($attachment->path), 404, 'Arquivo não encontrado');

        return $storage->response($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type,
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }

    public function destroy(Request $request, AppointmentAttachment $attachment): JsonResponse
    {
        $this->authorizeAttachment($request, $attachment);

        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();

        return response()->json(['message' => 'Anexo removido com sucesso']);
    }
}
