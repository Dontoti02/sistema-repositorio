<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;

/**
 * Documentación y asistencia del panel (6.20): ayuda contextual,
 * manual por rol, preguntas frecuentes y políticas de soporte.
 */
final class HelpController extends AdminController
{
    /** @return array<string,string> */
    private const SECTIONS = [
        'inicio' => 'Escritorio',
        'recursos' => 'Recursos',
        'deposito' => 'Depósito de recursos',
        'revision' => 'Revisión editorial',
        'comunidades' => 'Comunidades y colecciones',
        'calidad' => 'Calidad de recursos',
        'metadatos' => 'Metadatos y perfiles',
        'tipos-documentales' => 'Tipos documentales',
        'programas' => 'Programas',
        'usuarios' => 'Usuarios',
        'roles' => 'Roles y permisos',
        'institucion' => 'Ficha institucional',
        'importar-exportar' => 'Importar y exportar',
        'reportes' => 'Reportes y estadísticas',
        'auditoria' => 'Bitácora de auditoría',
        'notificaciones' => 'Notificaciones',
        'sistema' => 'Sistema',
        'ayuda' => 'Ayuda',
    ];

    /** @return array<string,string> */
    private const TIPS = [
        'inicio' => 'El escritorio resume pendientes, actividad reciente y accesos rápidos. Si algo falta, verifique su rol en «Roles y permisos».',
        'recursos' => 'Busque por título, estado o colección. Use «Nuevo recurso» para depositar; el flujo pasa por borrador, revisión y aprobación.',
        'deposito' => 'Complete título, resumen, idioma, fecha y tipo documental antes de enviar a revisión. Guarde con frecuencia: el borrador conserva sus cambios.',
        'revision' => 'Asigne revisiones a revisores, registre comentarios visibles para el depositante y use «Solicitar cambios» o «Aprobar» según corresponda.',
        'comunidades' => 'Cree comunidades y colecciones; el orden define cómo se muestran en el portal público.',
        'calidad' => 'Los validadores señalan hallazgos con severidad y propuesta de corrección. Pulse «Recalcular puntuaciones» para persistir el puntaje de cada recurso.',
        'metadatos' => 'El diccionario define campos globales; los perfiles de metadatos determinan qué campos son obligatorios al depositar. Duplique un perfil para versionarlo.',
        'tipos-documentales' => 'Los tipos documentales controlan qué campos adicionales se solicitan (por ejemplo, tesis). Marque «requiere campos de grado» solo para títulos.',
        'programas' => 'Los programas y dependencias se asocian a los recursos para estadísticas por área.',
        'usuarios' => 'Cree usuarios, asígneles roles y suspenda cuentas inactivas. El acceso se audita en la bitácora.',
        'roles' => 'Los roles agrupan permisos. Modifique con cuidado: los cambios afectan a todos los usuarios con ese rol.',
        'institucion' => 'Aquí se gestiona la ficha institucional, colores, licencias y embargos por defecto.',
        'importar-exportar' => 'Suba un CSV con encabezados, mapee las columnas, revise el prevalidado (fila por fila) e importe. La importación se puede revertir si el lote sigue en borrador.',
        'reportes' => 'Filtre por estado, colección y fechas; exporte el reporte en CSV para su análisis externo.',
        'auditoria' => 'La bitácora registra acciones sensibles con usuario, IP enmascarada y resumen del cambio. Exporte el CSV para conservancia.',
        'notificaciones' => 'Revise el buzón y configure qué avisos desea recibir por correo o en el panel.',
        'sistema' => 'Salud del sistema, cola de trabajos, tareas programadas y respaldos. Solo personal autorizado.',
        'ayuda' => 'Esta página: guías por rol, preguntas frecuentes y canales de soporte.',
    ];

    public function index(Request $request): Response
    {
        $current = $request->str('seccion', '');
        if (!array_key_exists($current, self::SECTIONS)) {
            $current = '';
        }
        return $this->view('admin/help/index', [
            'pageTitle' => 'Ayuda y documentación',
            'sections' => self::SECTIONS,
            'tips' => self::TIPS,
            'current' => $current,
            'currentTitle' => $current !== '' ? self::SECTIONS[$current] : null,
        ]);
    }
}
