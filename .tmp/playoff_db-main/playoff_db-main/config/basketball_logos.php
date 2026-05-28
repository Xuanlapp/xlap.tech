<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Basketball Logo Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for basketball logo paths and naming conventions.
    | Each logo type has a specific path pattern and may have special naming rules.
    |
    */

    'nba' => [
        'logo_types' => [
            'a_NBA_COLOR' => [
                'path' => 'Prepress5/PP_Masters/Logos/BASKETBALL/_NBA_MASTER_LOGOS/a_NBA_COLOR',
                'suffix' => 'A',
                'description' => 'NBA Color Logo'
            ],
            'b_NBA_COLOR_WHT' => [
                'path' => 'Prepress5/PP_Masters/Logos/BASKETBALL/_NBA_MASTER_LOGOS/b_NBA_COLOR_WHT',
                'suffix' => 'B',
                'description' => 'NBA Color White Logo'
            ],
            'c_NBA_5THK' => [
                'path' => 'Prepress5/PP_Masters/Logos/BASKETBALL/_NBA_MASTER_LOGOS/c_NBA_5THK',
                'suffix' => 'C',
                'description' => 'NBA 5 Thick Logo'
            ],
            'cc_NBA_Multi_Black' => [
                'path' => 'Prepress5/PP_Masters/Logos/BASKETBALL/_NBA_MASTER_LOGOS/cc_NBA_Multi_Black',
                'suffix' => 'cc',
                'description' => 'NBA Multi Black Logo'
            ],
            'd_NBA_FOIL_ETCH' => [
                'path' => 'Prepress5/PP_Masters/Logos/BASKETBALL/_NBA_MASTER_LOGOS/d_NBA_FOIL_ETCH',
                'suffix' => 'D',
                'description' => 'NBA Foil Etch Logo'
            ],
            'e_NBA_SPOT' => [
                'path' => 'Prepress5/PP_Masters/Logos/BASKETBALL/_NBA_MASTER_LOGOS/e_NBA_SPOT',
                'suffix' => 'E',
                'description' => 'NBA Spot Logo'
            ],
            'f_NBA_KO' => [
                'path' => 'Prepress5/PP_Masters/Logos/BASKETBALL/_NBA_MASTER_LOGOS/f_NBA_KO',
                'suffix' => 'F',
                'description' => 'NBA Knockout Logo'
            ],
            'g_NBA_KO_WHT' => [
                'path' => 'Prepress5/PP_Masters/Logos/BASKETBALL/_NBA_MASTER_LOGOS/g_NBA_KO_WHT',
                'suffix' => 'G',
                'description' => 'NBA Knockout White Logo'
            ],
            'i_NBA_PRIZM' => [
                'path' => 'Prepress5/PP_Masters/Logos/BASKETBALL/_NBA_MASTER_LOGOS/i_NBA_PRIZM',
                'suffix' => 'I',
                'description' => 'NBA Prizm Logo'
            ],
            'ii_NBA_PRIZM 5WHT' => [
                'path' => 'Prepress5/PP_Masters/Logos/BASKETBALL/_NBA_MASTER_LOGOS/ii_NBA_PRIZM 5WHT',
                'suffix' => 'I',
                'description' => 'NBA Prizm 5 White Logo'
            ],
            'j_Wordmark_KO' => [
                'path' => 'Prepress5/PP_Masters/Logos/BASKETBALL/_NBA_MASTER_LOGOS/j_Wordmark_KO',
                'suffix' => 'J',
                'description' => 'NBA Wordmark Knockout Logo'
            ],
            'jj_Wordmark_KO_WHT' => [
                'path' => 'Prepress5/PP_Masters/Logos/BASKETBALL/_NBA_MASTER_LOGOS/jj_Wordmark_KO_WHT',
                'suffix' => 'JJ',
                'description' => 'NBA Wordmark Knockout White Logo'
            ],
            'k_NBA_FOIL' => [
                'path' => 'Prepress5/PP_Masters/Logos/BASKETBALL/_NBA_MASTER_LOGOS/k_NBA_FOIL',
                'suffix' => 'K',
                'description' => 'NBA Foil Logo'
            ],
            'kk_NBA_FOIL_ETCH_KO' => [
                'path' => 'Prepress5/PP_Masters/Logos/BASKETBALL/_NBA_MASTER_LOGOS/kk_NBA_FOIL_ETCH_KO',
                'suffix' => 'KK',
                'description' => 'NBA Foil Etch Knockout Logo'
            ],
            'nn_Wordmark - ONE COLOR' => [
                'path' => 'Prepress5/PP_Masters/Logos/BASKETBALL/_NBA_MASTER_LOGOS/nn_Wordmark - ONE COLOR',
                'suffix' => 'N',
                'description' => 'NBA Wordmark One Color Logo'
            ],
            'p_NBA_RC_COLOR' => [
                'path' => 'Prepress5/PP_Masters/Logos/BASKETBALL/_NBA_MASTER_LOGOS/p_NBA_RC_COLOR',
                'suffix' => 'P',
                'description' => 'NBA RC Color Logo',
                'is_rc' => true,
                'file_pattern' => [
                    'primary' => '{pickup_name}_RC_{suffix}1.ai',
                    'secondary' => '{pickup_name}_RC_{suffix}2.ai'
                ]
            ],
            'q_NBA_RC_COLOR_WHT' => [
                'path' => 'Prepress5/PP_Masters/Logos/BASKETBALL/_NBA_MASTER_LOGOS/q_NBA_RC_COLOR_WHT',
                'suffix' => 'Q',
                'description' => 'NBA RC Color White Logo',
                'is_rc' => true,
                'file_pattern' => [
                    'primary' => '{pickup_name}_RC_{suffix}1.ai',
                    'secondary' => '{pickup_name}_RC_{suffix}2.ai'
                ]
            ],
            'qc_NBA_RC_COLOR_WHT' => [
                'path' => 'Prepress5/PP_Masters/Logos/BASKETBALL/_NBA_MASTER_LOGOS/qc_NBA_RC_COLOR_WHT',
                'suffix' => 'QC',
                'description' => 'NBA RC Color White Logo (QC)',
                'is_rc' => true,
                'file_pattern' => [
                    'primary' => '{pickup_name}_RC_{suffix}1.ai',
                    'secondary' => '{pickup_name}_RC_{suffix}2.ai'
                ]
            ],
            'r_NBA_RC_WHT_ETCH' => [
                'path' => 'Prepress5/PP_Masters/Logos/BASKETBALL/_NBA_MASTER_LOGOS/r_NBA_RC_WHT_ETCH',
                'suffix' => 'R',
                'description' => 'NBA RC White Etch Logo',
                'is_rc' => true,
                'file_pattern' => [
                    'primary' => '{pickup_name}_RC_{suffix}1.ai',
                    'secondary' => '{pickup_name}_RC_{suffix}2.ai'
                ]
            ],
            's_NBA_RC_FOIL_TC' => [
                'path' => 'Prepress5/PP_Masters/Logos/BASKETBALL/_NBA_MASTER_LOGOS/s_NBA_RC_FOIL_TC',
                'suffix' => 'S',
                'description' => 'NBA RC Foil TC Logo',
                'is_rc' => true,
                'file_pattern' => [
                    'primary' => '{pickup_name}_RC_{suffix}1.ai',
                    'secondary' => '{pickup_name}_RC_{suffix}2.ai'
                ]
            ],
            'sa_NBA_RC_FOIL_TC_WHT' => [
                'path' => 'Prepress5/PP_Masters/Logos/BASKETBALL/_NBA_MASTER_LOGOS/sa_NBA_RC_FOIL_TC_WHT',
                'suffix' => 'SA',
                'description' => 'NBA RC Foil TC White Logo',
                'is_rc' => true,
                'file_pattern' => [
                    'primary' => '{pickup_name}_RC_{suffix}1.ai',
                    'secondary' => '{pickup_name}_RC_{suffix}2.ai'
                ]
            ],
            't_NBA_TEAM_COLOR' => [
                'path' => 'Prepress5/PP_Masters/Logos/BASKETBALL/_NBA_MASTER_LOGOS/t_NBA_TEAM_COLOR',
                'suffix' => 'T',
                'description' => 'NBA Team Color Logo',
                'is_team_color' => true,
                'file_pattern' => [
                    'primary' => '{pickup_name}_{year}_{init_letters}{year_code}A1_TC1.ai',
                    'secondary' => '{pickup_name}_{year}_{init_letters}{year_code}A1_TC2.ai'
                ]
            ],
            'v_NBA_Gold_Logos' => [
                'path' => 'Prepress5/PP_Masters/Logos/BASKETBALL/_NBA_MASTER_LOGOS/v_NBA_Gold_Logos',
                'suffix' => 'V',
                'description' => 'NBA Gold Logo'
            ],
            'vv_NBA_Gold_Logos_WHT' => [
                'path' => 'Prepress5/PP_Masters/Logos/BASKETBALL/_NBA_MASTER_LOGOS/vv_NBA_Gold_Logos WHT',
                'suffix' => 'V',
                'description' => 'NBA Gold White Logo'
            ],
        ],

        // Default file naming pattern for regular logos
        'file_pattern' => '{pickup_name}_{year}_{init_letters}{year_code}{suffix}1.ai',

        // Color types
        'color_types' => [
            'primary' => 1,   // Primary color (represented by 1 in filename)
            'secondary' => 2, // Secondary color (represented by 2 in filename)
        ],

        // Special pattern types
        'pattern_types' => [
            'rc' => [
                'description' => 'Rookie Logo',
                'pattern' => '{pickup_name}_RC_{suffix}{color}.ai'
            ],
            'team_color' => [
                'description' => 'Team Color Logo',
                'pattern' => '{pickup_name}_{year}_{init_letters}{year_code}A1_TC{color}.ai'
            ],
        ],
    ],

    // Add other sports here in the future (WNBA, NFL, etc.)
];
