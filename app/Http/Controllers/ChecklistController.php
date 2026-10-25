<?php

namespace App\Http\Controllers;

use App\Models\ChecklistTemplateItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Template checklist commissioning / recommissioning. Perubahan hanya berlaku untuk proses BARU (proses lama menyimpan salinannya). */
class ChecklistController extends Controller
{
    public function index(Request $request)
    {
        $scope = (string) $request->query('scope');
        $category = (string) $request->query('kategori');

        $items = ChecklistTemplateItem::query()->orderBy('sort_order')->orderBy('id')
            ->when(in_array($scope, ['both', 'commissioning', 'recommissioning'], true), fn ($q) => $q->where('scope', $scope))
            ->get()
            ->filter(fn ($i) => $category === '' || ! $i->categoryList() || in_array($category, $i->categoryList(), true));

        return view('cms.checklist', [
            'groups' => $items->groupBy('group_name'), 'scope' => $scope, 'category' => $category,
            'canEdit' => (bool) portal_user()->hasRole('admin'),
            'total' => ChecklistTemplateItem::count(), 'active' => ChecklistTemplateItem::where('is_active', true)->count(),
        ]);
    }

    private function rules(): array
    {
        return [
            'scope' => ['required', Rule::in(['both', 'commissioning', 'recommissioning'])],
            'categories' => ['nullable', 'array'], 'categories.*' => [Rule::in(array_keys(config('commissioning.categories')))],
            'reasons' => ['nullable', 'array'], 'reasons.*' => [Rule::in(array_keys(config('commissioning.reasons')))],
            'group_name' => ['required', 'string', 'max:80'],
            'label' => ['required', 'string', 'max:255'],
            'is_critical' => ['nullable', 'boolean'],
            'reference' => ['nullable', 'string', 'max:120'],
        ];
    }

    private const MESSAGES = ['group_name.required' => 'Kelompok wajib diisi.', 'label.required' => 'Isi item wajib diisi.', 'scope.required' => 'Pilih berlaku untuk.'];

    private function payload(Request $request): array
    {
        $data = $request->validate($this->rules(), self::MESSAGES);
        $cats = $data['categories'] ?? [];
        $reasons = $data['reasons'] ?? [];
        // Alasan hanya masuk akal untuk recommissioning.
        if ($data['scope'] === 'commissioning') {
            $reasons = [];
        }

        return [
            'scope' => $data['scope'], 'categories' => $cats ? implode(',', $cats) : 'all', 'reasons' => $reasons ? implode(',', $reasons) : null,
            'group_name' => $data['group_name'], 'label' => $data['label'], 'is_critical' => $request->boolean('is_critical'), 'reference' => $data['reference'] ?? null,
        ];
    }

    public function store(Request $request)
    {
        $payload = $this->payload($request);
        $payload['sort_order'] = ((int) ChecklistTemplateItem::max('sort_order')) + 10;
        ChecklistTemplateItem::create($payload + ['is_active' => true]);

        return redirect()->route('cms.checklist')->with('success', 'Item checklist ditambahkan. Berlaku untuk proses baru.');
    }

    public function update(Request $request, ChecklistTemplateItem $item)
    {
        $item->update($this->payload($request));

        return redirect()->route('cms.checklist')->with('success', 'Item checklist diperbarui. Berlaku untuk proses baru.');
    }

    public function toggle(ChecklistTemplateItem $item)
    {
        $item->update(['is_active' => ! $item->is_active]);

        return redirect()->route('cms.checklist')->with('success', $item->is_active ? 'Item diaktifkan.' : 'Item dinonaktifkan (tidak masuk proses baru).');
    }
}
