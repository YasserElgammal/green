<?php

namespace App\Controllers\Web\Admin;

use App\Middleware\AdminMiddleware;
use App\Payloads\AdminStoreUserPayload;
use App\Payloads\AdminUpdateUserPayload;
use App\Tables\UserTable;
use YasserElgammal\Green\Database\Database;
use YasserElgammal\Green\Routing\Route;

class UserController extends BaseAdminController
{
    #[Route('GET', '/admin/users', [AdminMiddleware::class])]
    public function index()
    {
        $search = $this->query('search');
        $sort = $this->sort(['id', 'name', 'email', 'is_admin', 'created_at'], 'id');
        $direction = $this->direction();

        $result = $this->paginateArray([], self::PER_PAGE, 1);

        try {
            $users = new UserTable();
            $users->includeCount('posts');
            $query = $users->builder();

            if ($search !== '') {
                $query->where('name LIKE :search OR email LIKE :search')
                    ->setParameter('search', "%{$search}%");
            }

            $query->orderBy($sort, $direction);
            $result = $users->paginateFromBuilder($query, self::PER_PAGE, $this->page());
        } catch (\Throwable) {
            session()->flash('error', 'Unable to load users. Make sure migrations are up to date.');
        }

        return view('admin/users/index', [
            'users' => $result['data'],
            'meta' => $result['meta'],
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'nextDirection' => $direction === 'ASC' ? 'DESC' : 'ASC',
        ]);
    }

    #[Route('GET', '/admin/users/create', [AdminMiddleware::class])]
    public function create()
    {
        return view('admin/users/form', ['user' => null, 'mode' => 'create']);
    }

    #[Route('POST', '/admin/users', [AdminMiddleware::class])]
    public function store(AdminStoreUserPayload $payload)
    {
        $data = $payload->validated();

        try {
            $users = new UserTable();

            if ($users->fetchFirst('email', $data['email'])) {
                session()->flash('error', 'Email already exists.');
                return redirect('/admin/users/create');
            }

            $users->insert([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                'is_admin' => $data['is_admin'],
            ]);

            session()->flash('success', 'User created successfully.');
        } catch (\Throwable $e) {
            session()->flash('error', 'Unable to create user: ' . $e->getMessage());
        }

        return redirect('/admin/users');
    }

    #[Route('GET', '/admin/users/{id}/edit', [AdminMiddleware::class])]
    public function edit(int $id)
    {
        $user = (new UserTable())->fetchById($id);

        if (!$user) {
            session()->flash('error', 'User not found.');
            return redirect('/admin/users');
        }

        return view('admin/users/form', ['user' => $user, 'mode' => 'edit']);
    }

    #[Route('POST', '/admin/users/{id}', [AdminMiddleware::class])]
    public function update(int $id, AdminUpdateUserPayload $payload)
    {
        $data = $payload->validated();
        $users = new UserTable();
        $user = $users->fetchById($id);

        if (!$user) {
            session()->flash('error', 'User not found.');
            return redirect('/admin/users');
        }

        if ((int) $user->is_admin === 1 && (int) $data['is_admin'] !== 1 && $this->adminCount() <= 1) {
            session()->flash('error', 'You cannot remove admin access from the last remaining admin.');
            return redirect("/admin/users/{$id}/edit");
        }

        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'is_admin' => $data['is_admin'],
        ];

        if ($data['password']) {
            $updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $users->update($id, $updateData);
        session()->flash('success', 'User updated successfully.');

        return redirect('/admin/users');
    }

    #[Route('POST', '/admin/users/{id}/toggle-admin', [AdminMiddleware::class])]
    public function toggleAdmin(int $id)
    {
        $users = new UserTable();
        $user = $users->fetchById($id);

        if (!$user) {
            session()->flash('error', 'User not found.');
            return redirect('/admin/users');
        }

        $isAdmin = (int) $user->is_admin === 1;
        if ($isAdmin && $this->adminCount() <= 1) {
            session()->flash('error', 'You cannot remove admin access from the last remaining admin.');
            return redirect('/admin/users');
        }

        $users->update($id, ['is_admin' => $isAdmin ? 0 : 1]);
        session()->flash('success', 'Admin access updated.');

        return redirect('/admin/users');
    }

    #[Route('POST', '/admin/users/{id}/delete', [AdminMiddleware::class])]
    public function delete(int $id)
    {
        $users = new UserTable();
        $user = $users->fetchById($id);

        if (!$user) {
            session()->flash('error', 'User not found.');
            return redirect('/admin/users');
        }

        if ((int) $user->is_admin === 1 && $this->adminCount() <= 1) {
            session()->flash('error', 'You cannot delete the last remaining admin.');
            return redirect('/admin/users');
        }

        $users->deleteById($id);
        session()->flash('success', 'User deleted successfully.');

        return redirect('/admin/users');
    }

    private function adminCount(): int
    {
        try {
            return (int) Database::getConnection()
                ->createQueryBuilder()
                ->select('COUNT(*)')
                ->from('users')
                ->where('is_admin = 1')
                ->executeQuery()
                ->fetchOne();
        } catch (\Throwable) {
            return 0;
        }
    }
}
