<?php

return [
    // Nivel → modo de evaluación y parámetros
    'niveles' => [
        'inicial' => [
            'type' => 'QUALITATIVE',
            'qual' => [
                'niveles' => ['EN_PROCESO','LOGRADO','DESTACADO','OTRO'],
            ],
        ],
        'primaria' => [
            'type' => 'NUMERIC',
            'num'  => [
                'minNota' => 0,
                'maxNota' => 100,
                'aprobatoria' => 51,
                'decimales' => 0,
                'asistenciaMin' => 0,
            ],
        ],
        'secundaria' => [
            'type' => 'NUMERIC',
            'num'  => [
                'minNota' => 0,
                'maxNota' => 100,
                'aprobatoria' => 51,
                'decimales' => 0,
                'asistenciaMin' => 0,
            ],
        ],
    ],
];
