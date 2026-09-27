<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Experience;
use App\Models\Plan;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $categories = Category::orderBy('sort_order')->get();
        $activeCategory = $categories->firstWhere('slug', $request->query('cat'));

        $experiences = Experience::published()
            ->with(['host.user.avatar', 'host.plan', 'category', 'province', 'cover'])
            ->when($activeCategory, fn ($q) => $q->where('category_id', $activeCategory->id))
            ->when($request->filled('lugar'), function ($query) use ($request) {
                $place = '%'.$request->string('lugar')->trim()->toString().'%';
                $query->where(fn ($q) => $q->where('city', 'ilike', $place)->orWhereHas('province', fn ($p) => $p->where('name', 'ilike', $place)));
            })
            ->orderByDesc('rating_avg')
            ->orderByDesc('reviews_count')
            ->get();

        return view('home', [
            'categories' => $categories,
            'activeCategory' => $activeCategory,
            'experiences' => $experiences,
            'plans' => Plan::where('is_active', true)->orderBy('sort_order')->get(),
            'testimonials' => Review::with(['user.avatar', 'experience'])->whereNotNull('published_at')->where('rating', '>=', 5)->latest('published_at')->take(3)->get(),
            'stats' => [
                'experiences' => Experience::published()->count(),
                'reviews' => Review::whereNotNull('published_at')->count(),
                'rating' => round((float) Experience::published()->where('reviews_count', '>', 0)->avg('rating_avg'), 1),
            ],
        ]);
    }
}
