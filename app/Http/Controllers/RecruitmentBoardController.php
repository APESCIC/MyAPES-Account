<?php

namespace App\Http\Controllers;

use App\Models\RecruitmentApplication;
use App\Models\RecruitmentRole;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RecruitmentBoardController extends Controller
{
    public function index(Request $request): View
    {
        $category = $request->query('category');
        if (is_string($category) && $category !== '' && ! in_array($category, RecruitmentRole::CATEGORIES, true)) {
            abort(404);
        }

        $selectedCategory = is_string($category) && $category !== ''
            ? $category
            : null;

        $roles = RecruitmentRole::query()
            ->open()
            ->when(
                $selectedCategory !== null,
                static fn ($query) => $query->where('category', $selectedCategory),
            )
            ->latest('published_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('recruitment.index', [
            'roles' => $roles,
            'categories' => RecruitmentRole::CATEGORIES,
            'selectedCategory' => $selectedCategory,
            'categoryLabels' => $this->categoryLabels(),
        ]);
    }

    public function show(Request $request, RecruitmentRole $recruitmentRole): View
    {
        abort_unless($recruitmentRole->isOpen(), 404);

        $user = $request->user();
        $existingApplication = null;
        $canApply = false;

        if ($user !== null) {
            $existingApplication = RecruitmentApplication::query()
                ->where('recruitment_role_id', $recruitmentRole->id)
                ->where('user_id', $user->id)
                ->first();
            $canApply = $existingApplication === null
                && Gate::forUser($user)->allows('create', [RecruitmentApplication::class, $recruitmentRole]);
        }

        return view('recruitment.show', [
            'role' => $recruitmentRole,
            'categoryLabels' => $this->categoryLabels(),
            'existingApplication' => $existingApplication,
            'canApply' => $canApply,
            'statusLabels' => [
                RecruitmentApplication::STATUS_SUBMITTED => 'Submitted',
                RecruitmentApplication::STATUS_UNDER_REVIEW => 'Under review',
                RecruitmentApplication::STATUS_SHORTLISTED => 'Shortlisted',
                RecruitmentApplication::STATUS_REJECTED => 'Rejected',
                RecruitmentApplication::STATUS_ACCEPTED => 'Accepted',
                RecruitmentApplication::STATUS_WITHDRAWN => 'Withdrawn',
                RecruitmentApplication::STATUS_CLOSED => 'Closed',
            ],
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function categoryLabels(): array
    {
        return [
            'staff' => 'Staff',
            'volunteer' => 'Volunteer',
            'student' => 'Student',
        ];
    }
}
