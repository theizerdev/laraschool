# Arquitectura del Sistema Vargas

## Introducción

El sistema Vargas es una plataforma de gestión educativa construida con Laravel que implementa patrones de diseño orientados a servicios y repositorios para lograr una arquitectura limpia y mantenible.

## Capas de la Aplicación

### 1. Presentación (Livewire Components)
- Responsables de la interfaz de usuario
- Gestionan el estado de la UI
- Se comunican con servicios para realizar operaciones

### 2. Servicios (Services)
- Contienen la lógica de negocio compleja
- Coordinan operaciones entre diferentes repositorios
- Implementan reglas de negocio específicas

### 3. Repositorios (Repositories)
- Abstraen el acceso a datos
- Centralizan operaciones CRUD
- Proporcionan métodos específicos de consulta

### 4. Modelos (Eloquent Models)
- Representan la capa de dominio
- Definen relaciones entre entidades
- Implementan lógica de dominio simple

## Convenciones Importantes

### Relaciones
- Todas las relaciones en español: `estudiante`, `programa`, `periodo`
- Alias en inglés permitidos para compatibilidad: `student`, `schoolPeriod`
- Priorizar el uso de relaciones en español

### Patrones de Diseño

#### Repositorio
```php
interface BaseRepositoryInterface
{
    public function all();
    public function find($id);
    public function create(array $attributes);
    public function update($id, array $attributes);
    public function delete($id);
    public function paginate($perPage = 15);
}
```

#### Inyección de Dependencias
- Usar interfaces para la inyección de dependencias
- Registrar bindings en RepositoryServiceProvider
- Evitar acoplamiento directo con implementaciones concretas

## Estructura de Directorios

```
app/
├── Livewire/          # Componentes de UI
├── Services/          # Lógica de negocio
├── Repositories/      # Acceso a datos
│   └── Contracts/     # Interfaces de repositorios
├── Models/            # Modelos de dominio
└── Providers/         # Proveedores de servicios
```

## Principios de Desarrollo

1. **Inversión de Dependencias**: Depender de abstracciones, no de implementaciones concretas
2. **Responsabilidad Única**: Cada clase debe tener una sola razón para cambiar
3. **Abierto/Cerrado**: Extensible sin modificar código existente
4. **Sustitución de Liskov**: Subtipos deben ser sustituibles por sus tipos base
5. **Segregación de Interfaces**: Interfaces pequeñas y específicas

## Buenas Prácticas

- Utilizar inyección de dependencias para todos los repositorios y servicios
- Implementar validaciones en los servicios antes de operaciones críticas
- Usar métodos específicos en lugar de consultas genéricas cuando sea necesario
- Mantener consistencia en la nomenclatura de métodos y propiedades
- Documentar claramente las operaciones complejas

## Ejemplo de Uso

```php
class SomeLivewireComponent extends Component
{
    protected $matriculaService;

    public function __construct(MatriculaService $matriculaService)
    {
        $this->matriculaService = $matriculaService;
    }

    public function crearMatricula()
    {
        $datos = [
            // ... datos de la matrícula
        ];
        
        $cronograma = $this->matriculaService->generarCronogramaPagos(
            // ... parámetros
        );
        
        $matricula = $this->matriculaService->crearMatriculaConCronograma(
            $datos,
            $cronograma
        );
    }
}
```