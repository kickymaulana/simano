<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreCommentRequest;
use App\Models\Comment;
use App\Models\EvaluationPeriod;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommentController extends Controller
{
    public function index(User $target, Request $request): Response|RedirectResponse
    {
        abort_if(! $target->active, 404);

        $period = EvaluationPeriod::active()->first();
        $template = $target->evaluationTemplate()->where('active', true)->first();

        if (! $period || ! $template) {
            return to_route('targets.index')->withErrors(['evaluation' => 'Periode atau template evaluasi belum aktif.']);
        }

        $comments = Comment::query()
            ->where('target_id', $target->id)
            ->where('evaluation_period_id', $period->id)
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Comment $comment): array => [
                'id' => $comment->id,
                'body' => $comment->body,
                'created_at' => $comment->created_at?->format('d/m/Y H:i'),
                'can_delete' => $comment->user_id === $request->user()->id || $request->user()->hasAnyRole(['admin', 'hr']),
            ]);

        $userComment = Comment::query()
            ->where('user_id', $request->user()->id)
            ->where('target_id', $target->id)
            ->where('evaluation_period_id', $period->id)
            ->exists();

        return Inertia::render('Comments/Index', [
            'target' => $target->only(['id', 'name', 'avatar_url']),
            'period' => $period->only(['id', 'month', 'year']),
            'comments' => $comments,
            'userComment' => $userComment,
        ]);
    }

    public function store(StoreCommentRequest $request, User $target): RedirectResponse
    {
        abort_if(! $target->active, 404);

        $period = EvaluationPeriod::active()->firstOrFail();
        $template = $target->evaluationTemplate()->where('active', true)->first();
        abort_unless($template, 404);

        try {
            Comment::create([
                'user_id' => $request->user()->id,
                'target_id' => $target->id,
                'evaluation_period_id' => $period->id,
                'body' => $request->validated('body'),
            ]);
        } catch (UniqueConstraintViolationException) {
            return to_route('comments.index', $target)->withErrors(['body' => 'Anda sudah mengirim komentar. Hapus komentar lama untuk membuat komentar baru.']);
        }

        return to_route('comments.index', $target)->with('success', 'Komentar berhasil dikirim.');
    }

    public function destroy(Request $request, Comment $comment): RedirectResponse
    {
        $user = $request->user();
        abort_unless($comment->user_id === $user->id || $user->hasAnyRole(['admin', 'hr']), 403);

        $targetId = $comment->target_id;
        $comment->delete();

        return to_route('comments.index', $targetId)->with('success', 'Komentar berhasil dihapus.');
    }
}
