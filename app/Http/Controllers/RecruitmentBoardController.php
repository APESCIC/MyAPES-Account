<?php

namespace App\Http\Controllers;

use App\Models\RecruitmentRole;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

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

    public function show(RecruitmentRole $recruitmentRole): View
    {
        abort_unless($recruitmentRole->isOpen(), 404);

        return view('recruitment.show', [
            'role' => $recruitmentRole,
            'categoryLabels' => $this->categoryLabels(),
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
