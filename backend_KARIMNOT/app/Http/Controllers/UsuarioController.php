<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UsuarioController extends Controller
{

    /**
    * POST /login - Autentica un usuario y devuelve un token
    */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validación fallida',
                    'detalle' => $validator->errors(),
                ], 422);
            }

            $usuario = Usuario::where('email', $request->email)->first();

            if (!$usuario || !Hash::check($request->password, $usuario->password)) {
                return response()->json([
                    'error' => 'Credenciales incorrectas',
                ], 401);
            }

            if ($usuario->status === 'Inactive') {
                return response()->json([
                    'error' => 'Usuario inactivo, contacta al administrador',
                ], 403);
            }

            // Elimina tokens previos (opcional, evita acumulación)
            $usuario->tokens()->delete();

            // Crea el token Sanctum
            $token = $usuario->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login exitoso',
                'token' => $token,
                'usuario' => $usuario,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error al iniciar sesión',
                'detalle' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /logout - Revoca el token actual
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return response()->json(['message' => 'Sesión cerrada correctamente']);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error al cerrar sesión',
                'detalle' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * GET /users - Devuelve lista de usuarios paginada, filtrada y con búsqueda
     * Parámetros de consulta: page, limit, search
     */
    public function index(Request $request)
    {
        try {
            $query = Usuario::query();

            // 🔍 Búsqueda en múltiples campos
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('firstName', 'like', "%{$search}%")
                        ->orWhere('lastName', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phoneNumber', 'like', "%{$search}%");
                });
            }

            // 📄 Paginación con valores por defecto
            $limit = $request->filled('limit') ? $request->limit : 10;
            $limit = min($limit, 100); // Máximo 100 por seguridad

            $data = $query->orderBy('id', 'desc')->paginate($limit);

            return response()->json([
                'data' => $data->items(),
                'pagination' => [
                    'total' => $data->total(),
                    'per_page' => $data->perPage(),
                    'current_page' => $data->currentPage(),
                    'last_page' => $data->lastPage(),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error al obtener usuarios',
                'detalle' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /users - Crea un nuevo usuario
     */
    public function store(Request $request)
    {
        try {
            // Validación de datos
            $validator = Validator::make($request->all(), [
                'firstName' => 'required|string|max:255',
                'lastName' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email|max:255',
                'password' => 'required|string|min:8',
                'phoneNumber' => 'required|string|max:20',
                'role' => 'required|in:Admin,User',
                'status' => 'required|in:Active,Inactive',
                'address' => 'nullable|array',
                'address.street' => 'nullable|string|max:255',
                'address.number' => 'nullable|string|max:20',
                'address.city' => 'nullable|string|max:100',
                'address.postalCode' => 'nullable|string|max:20',
                'profilePicture' => 'nullable|url|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validación fallida',
                    'detalle' => $validator->errors(),
                ], 422);
            }

            // Crear usuario
            $usuario = Usuario::create([
                'firstName' => $request->firstName,
                'lastName' => $request->lastName,
                'email' => $request->email,
                'password' => $request->password, // Se hashea automáticamente por el cast
                'phoneNumber' => $request->phoneNumber,
                'role' => $request->role,
                'status' => $request->status,
                'address' => $request->address ?? null,
                'profilePicture' => $request->profilePicture ?? null,
            ]);

            return response()->json([
                'message' => 'Usuario creado correctamente',
                'usuario' => $usuario,
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error al crear el usuario',
                'detalle' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /users/:id - Obtiene un usuario por ID
     */
    public function show($id)
    {
        try {
            $usuario = Usuario::findOrFail($id);
            return response()->json([
                'data' => $usuario,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Usuario no encontrado',
                'detalle' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * PUT /users/:id - Actualiza un usuario existente
     */
    public function update(Request $request, $id)
    {
        try {
            $usuario = Usuario::findOrFail($id);

            // Validación de datos
            $validator = Validator::make($request->all(), [
                'firstName' => 'sometimes|string|max:255',
                'lastName' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $id . '|max:255',
                'password' => 'sometimes|string|min:8',
                'phoneNumber' => 'sometimes|string|max:20',
                'role' => 'sometimes|in:Admin,User',
                'status' => 'sometimes|in:Active,Inactive',
                'address' => 'nullable|array',
                'address.street' => 'nullable|string|max:255',
                'address.number' => 'nullable|string|max:20',
                'address.city' => 'nullable|string|max:100',
                'address.postalCode' => 'nullable|string|max:20',
                'profilePicture' => 'nullable|url|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validación fallida',
                    'detalle' => $validator->errors(),
                ], 422);
            }

            // Actualizar solo los campos proporcionados
            $usuario->update($request->only([
                'firstName',
                'lastName',
                'email',
                'password',
                'phoneNumber',
                'role',
                'status',
                'address',
                'profilePicture',
            ]));

            return response()->json([
                'message' => 'Usuario actualizado correctamente',
                'usuario' => $usuario,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar el usuario',
                'detalle' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /users/:id - Elimina un usuario
     */
    public function destroy($id)
    {
        try {
            $usuario = Usuario::findOrFail($id);
            $usuario->delete();

            return response()->json([
                'message' => 'Usuario eliminado correctamente',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar el usuario',
                'detalle' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verifica si un email ya existe
     */
    public function verificarEmail($email)
    {
        try {
            $existe = Usuario::where('email', $email)->exists();
            return response()->json(['existe' => $existe]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error al verificar email',
                'detalle' => $e->getMessage(),
            ], 500);
        }
    }
}
