<?php

declare(strict_types=1);

return [
    'states' => [
        ['AGU', 'Aguascalientes', 'Bajío'],
        ['BCN', 'Baja California', 'Norte'],
        ['BCS', 'Baja California Sur', 'Norte'],
        ['CAM', 'Campeche', 'Sureste'],
        ['CHP', 'Chiapas', 'Sur'],
        ['CHH', 'Chihuahua', 'Norte'],
        ['CMX', 'Ciudad de México', 'Centro'],
        ['COA', 'Coahuila', 'Norte'],
        ['COL', 'Colima', 'Occidente'],
        ['DUR', 'Durango', 'Norte'],
        ['GUA', 'Guanajuato', 'Bajío'],
        ['GRO', 'Guerrero', 'Sur'],
        ['HID', 'Hidalgo', 'Centro'],
        ['JAL', 'Jalisco', 'Occidente'],
        ['MEX', 'Estado de México', 'Centro'],
        ['MIC', 'Michoacán', 'Occidente'],
        ['MOR', 'Morelos', 'Centro'],
        ['NAY', 'Nayarit', 'Occidente'],
        ['NLE', 'Nuevo León', 'Norte'],
        ['OAX', 'Oaxaca', 'Sur'],
        ['PUE', 'Puebla', 'Centro'],
        ['QUE', 'Querétaro', 'Bajío'],
        ['ROO', 'Quintana Roo', 'Sureste'],
        ['SLP', 'San Luis Potosí', 'Bajío'],
        ['SIN', 'Sinaloa', 'Norte'],
        ['SON', 'Sonora', 'Norte'],
        ['TAB', 'Tabasco', 'Sureste'],
        ['TAM', 'Tamaulipas', 'Norte'],
        ['TLA', 'Tlaxcala', 'Centro'],
        ['VER', 'Veracruz', 'Sureste'],
        ['YUC', 'Yucatán', 'Sureste'],
        ['ZAC', 'Zacatecas', 'Norte'],
    ],

    'crops' => [
        ['maiz', 'Maíz', 'Zea mays', '🌽', 'Poáceas', 'anual', 10],
        ['frijol', 'Frijol', 'Phaseolus vulgaris', '🫘', 'Fabáceas', 'anual', 20],
        ['trigo', 'Trigo', 'Triticum aestivum', '🌾', 'Poáceas', 'anual', 30],
        ['sorgo', 'Sorgo', 'Sorghum bicolor', '🌱', 'Poáceas', 'anual', 40],
        ['arroz', 'Arroz', 'Oryza sativa', '🍚', 'Poáceas', 'anual', 50],
        ['cebada', 'Cebada', 'Hordeum vulgare', '🍺', 'Poáceas', 'anual', 60],
        ['avena', 'Avena', 'Avena sativa', '🥣', 'Poáceas', 'anual', 70],
        ['cana', 'Caña de azúcar', 'Saccharum officinarum', '🎋', 'Poáceas', 'perenne', 80],
        ['cafe', 'Café', 'Coffea arabica', '☕', 'Rubiáceas', 'perenne', 90],
        ['agave', 'Agave', 'Agave tequilana', '🥃', 'Asparagáceas', 'perenne', 100],
        ['aguacate', 'Aguacate', 'Persea americana', '🥑', 'Lauráceas', 'perenne', 110],
        ['limon', 'Limón', 'Citrus × limon', '🍋', 'Rutáceas', 'perenne', 120],
        ['naranja', 'Naranja', 'Citrus × sinensis', '🍊', 'Rutáceas', 'perenne', 130],
        ['mango', 'Mango', 'Mangifera indica', '🥭', 'Anacardiáceas', 'perenne', 140],
        ['platano', 'Plátano', 'Musa × paradisiaca', '🍌', 'Musáceas', 'perenne', 150],
        ['jitomate', 'Jitomate', 'Solanum lycopersicum', '🍅', 'Solanáceas', 'anual', 160],
        ['chile', 'Chile', 'Capsicum annuum', '🌶️', 'Solanáceas', 'anual', 170],
        ['papa', 'Papa', 'Solanum tuberosum', '🥔', 'Solanáceas', 'anual', 180],
        ['cebolla', 'Cebolla', 'Allium cepa', '🧅', 'Amarilidáceas', 'bianual', 190],
        ['ajo', 'Ajo', 'Allium sativum', '🧄', 'Amarilidáceas', 'bianual', 200],
        ['sandia', 'Sandía', 'Citrullus lanatus', '🍉', 'Cucurbitáceas', 'anual', 210],
        ['melon', 'Melón', 'Cucumis melo', '🍈', 'Cucurbitáceas', 'anual', 220],
        ['pepino', 'Pepino', 'Cucumis sativus', '🥒', 'Cucurbitáceas', 'anual', 230],
        ['calabaza', 'Calabaza', 'Cucurbita pepo', '🎃', 'Cucurbitáceas', 'anual', 240],
        ['fresa', 'Fresa', 'Fragaria × ananassa', '🍓', 'Rosáceas', 'perenne', 250],
        ['vid', 'Vid', 'Vitis vinifera', '🍇', 'Vitáceas', 'perenne', 260],
        ['algodon', 'Algodón', 'Gossypium hirsutum', '🧵', 'Malváceas', 'anual', 270],
        ['soya', 'Soya', 'Glycine max', '🫛', 'Fabáceas', 'anual', 280],
        ['girasol', 'Girasol', 'Helianthus annuus', '🌻', 'Asteráceas', 'anual', 290],
        ['ajonjoli', 'Ajonjolí', 'Sesamum indicum', '🌿', 'Pedaliáceas', 'anual', 300],
        ['cacao', 'Cacao', 'Theobroma cacao', '🍫', 'Malváceas', 'perenne', 310],
        ['coco', 'Coco', 'Cocos nucifera', '🥥', 'Arecáceas', 'perenne', 320],
        ['durazno', 'Durazno', 'Prunus persica', '🍑', 'Rosáceas', 'perenne', 330],
        ['manzana', 'Manzana', 'Malus domestica', '🍎', 'Rosáceas', 'perenne', 340],
        ['nogal', 'Nogal', 'Juglans regia', '🌳', 'Juglandáceas', 'perenne', 350],
        ['guayaba', 'Guayaba', 'Psidium guajava', '🥝', 'Mirtáceas', 'perenne', 360],
        ['papaya', 'Papaya', 'Carica papaya', '🌴', 'Caricáceas', 'perenne', 370],
        ['pina', 'Piña', 'Ananas comosus', '🍍', 'Bromeliáceas', 'perenne', 380],
        ['brocoli', 'Brócoli', 'Brassica oleracea var. italica', '🥦', 'Brasicáceas', 'anual', 390],
        ['zanahoria', 'Zanahoria', 'Daucus carota', '🥕', 'Apiáceas', 'bianual', 400],
        ['lechuga', 'Lechuga', 'Lactuca sativa', '🥬', 'Asteráceas', 'anual', 410],
        ['garbanzo', 'Garbanzo', 'Cicer arietinum', '🧆', 'Fabáceas', 'anual', 420],
        ['cacahuate', 'Cacahuate', 'Arachis hypogaea', '🥜', 'Fabáceas', 'anual', 430],
        ['nopal', 'Nopal', 'Opuntia ficus-indica', '🌵', 'Cactáceas', 'perenne', 440],
        ['arandano', 'Arándano', 'Vaccinium corymbosum', '🫐', 'Ericáceas', 'perenne', 450],
        ['frambuesa', 'Frambuesa', 'Rubus idaeus', '🍒', 'Rosáceas', 'perenne', 460],
    ],

    'varieties' => [
        'maiz' => [
            'Criollo azul', 'Cacahuacintle', 'Criollo pozolero', 'Amarillo dentado',
            'H-377 (INIFAP)', 'Híbrido de temporal', 'Criollo blanco de milpa',
        ],
        'frijol' => [
            'Flor de mayo', 'Bayo madero', 'Negro Jamapa', 'Pinto Saltillo',
            'Azufrado pinto', 'Criollo canario', 'Flor de junio',
        ],
        'trigo' => [
            'Bárcena', 'Ciano-79', 'Sátevo', 'Romoga', 'Criollo',
        ],
        'sorgo' => [
            'Híbrido de grano blanco', 'Híbrido de grano rojo', 'Criollo bicolor',
            'Sorgo forrajero', 'Sudán',
        ],
        'arroz' => [
            'Morelos A-70', 'Morelos A-88', 'Sinaloa A-80', 'Criollo',
        ],
        'cebada' => [
            'Esmeralda', 'Gobernadora', 'Alina', 'Criolla',
        ],
        'avena' => [
            'Cuauhtémoc', 'Chihuahua', 'Teporaca', 'Bachiniva', 'Criolla',
        ],
        'cana' => [
            'CP 72-2086', 'Mex 69-290', 'Mex 79-431', 'CP 73-1547', 'RD 75-11',
        ],
        'cafe' => [
            'Typica', 'Bourbon', 'Caturra', 'Mundo Novo', 'Garnica',
            'Oro Azteca', 'Costa Rica 95',
        ],
        'agave' => [
            'Azul Tequilana Weber', 'Espadín', 'Tobalá', 'Salmiñana', 'Criollo',
        ],
        'aguacate' => [
            'Hass', 'Fuerte', 'Bacon', 'Pinkerton', 'Méndez', 'Criollo',
        ],
        'limon' => [
            'Persa', 'Italiano', 'Eureka', 'Rangpur', 'Criollo',
        ],
        'naranja' => [
            'Valencia', 'Navel', 'Hamlin', 'Cara Cara', 'Criolla',
        ],
        'mango' => [
            'Ataulfo', 'Manila', 'Kent', 'Tommy Atkins', 'Haden', 'Keitt',
        ],
        'platano' => [
            'Grand Naine', 'Giant Cavendish', 'Valery', 'Macho', 'Dominico', 'Criollo',
        ],
        'jitomate' => [
            'Roma', 'Saladette', 'Bola', 'Cherry', 'Híbrido comercial', 'Criollo',
        ],
        'chile' => [
            'Jalapeño', 'Serrano', 'Poblano', 'Ancho', 'Habanero',
            'Chilaca', 'Mirasol', 'Criollo',
        ],
        'papa' => [
            'Alpha', 'Fianna', 'Atlantic', 'Tollocan', 'Rosita', 'Norteña', 'Criolla',
        ],
        'cebolla' => [
            'Blanca de mayo', 'Roja de Zitácuaro', 'Amarilla', 'Cambray', 'Criolla',
        ],
        'ajo' => [
            'Morado de Zacatecas', 'Blanco', 'Perla', 'Chino', 'Criollo',
        ],
        'sandia' => [
            'Sugar Baby', 'Charleston Gray', 'Crimson Sweet', 'Jubilee', 'Criolla',
        ],
        'melon' => [
            'Cantaloupe', 'Honeydew', 'Charentais', 'Amarillo', 'Criollo',
        ],
        'pepino' => [
            'Criollo', 'Pepinillo', 'Híbrido comercial', 'Larga vida',
        ],
        'calabaza' => [
            'Calabacita', 'Zucchini', 'De castilla', 'Criolla',
        ],
        'fresa' => [
            'Camarosa', 'Chandler', 'Albion', 'Festival', 'Criolla',
        ],
        'vid' => [
            'Criolla', 'Cabernet Sauvignon', 'Merlot', 'Chardonnay',
            'Tempranillo', 'Malbec', 'Syrah',
        ],
        'algodon' => [
            'Delta Pine', 'Stoneville', 'Híbrido comercial', 'Criollo',
        ],
        'soya' => [
            'Huasteca', 'Cajeme', 'Júpiter', 'Tropical', 'Criolla',
        ],
        'girasol' => [
            'Victoria', 'Altasol', 'Híbrido comercial', 'Criollo',
        ],
        'ajonjoli' => [
            'Criollo blanco', 'Criollo negro', 'Blanco', 'Híbrido comercial',
        ],
        'cacao' => [
            'Criollo', 'Trinitario', 'Forastero', 'Mazorca de Indio', 'Cacao de Tabasco',
        ],
        'coco' => [
            'Alto del Pacífico', 'Enano Amarillo', 'Híbrido comercial', 'Criollo',
        ],
        'durazno' => [
            'Criollo', 'Oro A', 'Flordaking', 'Elegant Lady', 'Diamante',
        ],
        'manzana' => [
            'Golden Delicious', 'Red Delicious', 'Gala', 'Fuji', 'Anna', 'Criolla',
        ],
        'nogal' => [
            'Western', 'Chandler', 'Hartley', 'Pedro', 'Criollo',
        ],
        'guayaba' => [
            'Media China', 'China', 'Perucho', 'Roja', 'Criolla',
        ],
        'papaya' => [
            'Maradol', 'Tainung', 'Hawaiana', 'Amarilla', 'Criolla',
        ],
        'pina' => [
            'Cayena lisa', 'MD-2', 'Española', 'Queen', 'Criolla',
        ],
        'brocoli' => [
            'Calabrese', 'Híbrido comercial', 'Romanesco', 'Criollo',
        ],
        'zanahoria' => [
            'Chantenay', 'Nantes', 'Danvers', 'Híbrido comercial', 'Criolla',
        ],
        'lechuga' => [
            'Romana', 'Iceberg', 'Red Leaf', 'Híbrido comercial', 'Criolla',
        ],
        'garbanzo' => [
            'Kabuli', 'Blanco lechoso', 'Desi', 'Criollo',
        ],
        'cacahuate' => [
            'Valencia', 'Virginia', 'Rojo', 'Tennessee', 'Criollo',
        ],
        'nopal' => [
            'Blanco', 'Rojo', 'Verdura', 'Tuna', 'Criollo',
        ],
        'arandano' => [
            'Biloxi', 'Emerald', 'Jewel', 'Sharpblue', 'Criollo',
        ],
        'frambuesa' => [
            'Heritage', 'Tulameen', 'Willamette', 'Autumn Bliss', 'Criolla',
        ],
    ],

    'specialties' => [
        ['fitopatologia', 'Fitopatología'],
        ['entomologia-agricola', 'Entomología agrícola'],
        ['fertilidad-de-suelos', 'Fertilidad de suelos'],
        ['riego-y-drenaje', 'Riego y drenaje'],
        ['produccion-de-cultivos', 'Producción de cultivos'],
        ['horticultura-protegida', 'Horticultura protegida'],
        ['fruticultura', 'Fruticultura'],
        ['postcosecha', 'Postcosecha'],
        ['agroecologia', 'Agroecología'],
        ['mecanizacion-agricola', 'Mecanización agrícola'],
        ['sanidad-vegetal', 'Sanidad vegetal'],
        ['extension-agraria', 'Extensión agraria'],
        ['agrometeorologia', 'Agrometeorología'],
    ],

    'symptoms' => [
        // Follaje
        ['manchas-foliares', 'Manchas foliares', 'Follaje', 'Áreas decoloradas o necróticas dispersas en la lámina foliar, de forma y borde variables.'],
        ['manchas-angulares', 'Manchas angulares', 'Follaje', 'Manchas delimitadas por las nervaduras, con aspecto de ángulo recto, típicas de bacteriosis y mildius.'],
        ['manchas-con-halo', 'Manchas con halo', 'Follaje', 'Mancha necrótica rodeada por un halo clorótico o verde claro que indica toxinas o reacción de defensa.'],
        ['roya', 'Pústulas de roya', 'Follaje', 'Pústulas o polvillo anaranjado, amarillo o pardo en el envés o en el haz de la hoja.'],
        ['tizon-foliar', 'Tizón foliar', 'Follaje', 'Lesiones extensas, alargadas o en forma de puro, que secan la hoja desde la punta o el centro.'],
        ['mildiu-velloso', 'Mildiu velloso', 'Follaje', 'Manchas pálidas en el haz con eflorescencia blanquecina o grisácea en el envés.'],
        ['oidio', 'Oídio o ceniza', 'Follaje', 'Polvo blanco o cenizo sobre hojas, tallos y flores, que se frota con facilidad.'],
        ['antracnosis', 'Lesiones de antracnosis', 'Follaje', 'Lesiones hundidas, oscuras y con borde definido en hojas, tallos o frutos.'],
        ['clorosis', 'Clorosis', 'Follaje', 'Amarillamiento general o en manchas de la lámina foliar por pérdida de clorofila.'],
        ['clorosis-intervenal', 'Clorosis intervenal', 'Follaje', 'Amarillamiento entre nervaduras con los nervios verdes, típico de hierro o magnesio.'],
        ['necrosis-foliar', 'Necrosis foliar', 'Follaje', 'Tejido foliar muerto, pardo o gris, quebradizo y sin halo activo.'],
        ['defoliacion', 'Defoliación', 'Follaje', 'Caída prematura de hojas verdes o amarillas, desde la base o el interior de la planta.'],
        ['mosaico', 'Mosaico', 'Follaje', 'Patrón de manchas verde claro y verde oscuro, con distorsión o ampollamiento de la hoja.'],
        ['enrollamiento-foliar', 'Enrollamiento de hojas', 'Follaje', 'Hojas que se curvan hacia arriba o hacia abajo, a veces con engrosamiento y textura coriácea.'],
        ['minadores', 'Galerías de minador', 'Follaje', 'Galerías serpenteantes o ampollas translúcidas dentro de la hoja, con excremento visible.'],
        ['pulgones', 'Colonias de pulgones', 'Follaje', 'Insectos pequeños agrupados en brotes y envés, con melaza, fumagina y hormigas asociadas.'],
        ['mosca-blanca', 'Mosca blanca', 'Follaje', 'Adultos blancos y ninfas aplastadas en el envés; al mover la planta se levanta una nube de insectos.'],
        ['trips', 'Trips', 'Follaje', 'Rayado plateado, puntos negros de excremento y deformación de brotes y flores.'],
        ['acaros', 'Ácaros', 'Follaje', 'Punteado fino, bronceado y telaraña fina en el envés; hojas ásperas y descoloridas.'],
        ['gusanos-cortadores', 'Gusanos cortadores', 'Follaje', 'Plántulas cortadas al ras del suelo durante la noche, con tallos seccionados.'],
        ['cogollero', 'Larvas en el cogollo', 'Follaje', 'Larvas grandes dentro del cogollo, con excremento húmedo y hojas perforadas o rasgadas.'],
        ['barrenador', 'Barrenador', 'Follaje', 'Orificios de entrada con aserrín o excremento y galerías en tallos, frutos o cogollos.'],
        ['perforaciones-foliares', 'Perforaciones foliares', 'Follaje', 'Orificios pequeños y numerosos en la hoja por insectos masticadores, sin galería interna.'],
        ['fitotoxicidad-herbicida', 'Fitotoxicidad por herbicida', 'Follaje', 'Deformación, clorosis o necrosis con patrón de deriva o contacto, sin patógeno asociado.'],
        ['deficiencia-nitrogeno', 'Deficiencia de nitrógeno', 'Follaje', 'Amarillamiento uniforme de hojas viejas y crecimiento reducido, con tallos delgados.'],
        ['deficiencia-fosforo', 'Deficiencia de fósforo', 'Follaje', 'Hojas viejas con tinte morado o púrpura y crecimiento lento, raíces poco desarrolladas.'],
        ['deficiencia-potasio', 'Deficiencia de potasio', 'Follaje', 'Necrosis en márgenes de hojas viejas y frutos pequeños o de mala calidad.'],
        ['deficiencia-hierro', 'Deficiencia de hierro', 'Follaje', 'Clorosis intervenal en hojas jóvenes, con nervios verdes y lámina casi amarilla.'],
        ['deficiencia-zinc', 'Deficiencia de zinc', 'Follaje', 'Hojas nuevas pequeñas, en roseta, con clorosis intervenal y entrenudos cortos.'],
        ['deficiencia-magnesio', 'Deficiencia de magnesio', 'Follaje', 'Clorosis intervenal en hojas viejas, a veces con manchas necróticas y defoliación.'],
        ['desecacion-marginal', 'Desecación marginal', 'Follaje', 'Bordes de la hoja secos y pardos por salinidad, viento o déficit de agua.'],
        ['chinches', 'Chinches', 'Follaje', 'Picaduras oscuras, deformación de vainas o frutos y mal olor; insectos de olor penetrante.'],
        ['cochinilla', 'Cochinilla', 'Follaje', 'Masas algodonosas blancas o cerosas adheridas a cladodios, tallos u hojas.'],
        ['picudo', 'Picudo', 'Follaje', 'Adultos con pico alargado, orificios de alimentación y larvas en tallos o frutos.'],
        ['babosas', 'Babosas', 'Follaje', 'Mordidas irregulares y rastro de moco en plántulas y hojas bajas.'],

        // Tallo
        ['agallas', 'Agallas', 'Tallo', 'Tumores leñosos o carnosos en tallos, cuello o raíces, por bacterias o nematodos.'],
        ['cancros', 'Cancros', 'Tallo', 'Lesiones hundidas y oscuras en la corteza, con tejido muerto que puede anillar el tallo.'],
        ['exudados', 'Exudados', 'Tallo', 'Goma, savia o látex que escurre de grietas, heridas o lesiones del tronco.'],
        ['tallo-quebrado', 'Tallo quebrado', 'Tallo', 'Tallos que se doblan o rompen por viento, granizo o debilidad estructural.'],
        ['tallo-hueco', 'Tallo hueco', 'Tallo', 'Médula necrosada o hueca, con pared del tallo adelgazada y fácil de comprimir.'],
        ['podredumbre-basal', 'Pudrición basal', 'Tallo', 'Base del tallo blanda, oscura y con olor, que provoca marchitez y vuelco.'],
        ['pudricion-del-cuello', 'Pudrición del cuello', 'Tallo', 'Necrosis húmeda en la unión tallo-raíz, con corteza que se desprende.'],

        // Raíz
        ['agallas-radiculares', 'Agallas radiculares', 'Raíz', 'Engrosamientos irregulares en raíces, con pérdida de raicillas y marchitez de la planta.'],
        ['pudricion-radicular', 'Pudrición radicular', 'Raíz', 'Raíces pardas, blandas o descortezadas; el sistema radical se reduce y la planta marchita.'],
        ['nematodos', 'Nódulos de nematodo', 'Raíz', 'Nódulos o agallas pequeñas en raíces, con raicillas laterales escasas y plantas raquíticas.'],
        ['suberizacion-raiz', 'Suberización de raíces', 'Raíz', 'Raíces engrosadas, corchosas y con puntas romas por toxicidad o mal drenaje.'],
        ['raices-expuestas', 'Raíces expuestas', 'Raíz', 'Raíces superficiales visibles por erosión o labranza excesiva, sensibles a la sequía.'],

        // Fruto
        ['manchas-fruto', 'Manchas en fruto', 'Fruto', 'Manchas oscuras, hundidas o con halo en la superficie del fruto.'],
        ['pudricion-fruto', 'Pudrición de fruto', 'Fruto', 'Fruto blando, oscuro y con moho, que puede mostrar anillos concéntricos.'],
        ['momificacion-fruto', 'Momificación del fruto', 'Fruto', 'Frutos secos, arrugados y adheridos al árbol, que son fuente de inóculo.'],
        ['agrietamiento-fruto', 'Agrietamiento de fruto', 'Fruto', 'Grietas radiales o circulares en la cutícula por lluvia, riego irregular o calcio.'],
        ['deformacion-fruto', 'Deformación de fruto', 'Fruto', 'Frutos torcidos, abollados o en forma de media luna por daño en floración.'],
        ['caida-fruto', 'Caída de fruto', 'Fruto', 'Frutos pequeños o maduros que caen antes de la cosecha, a veces con pedúnculo amarillo.'],
        ['taladro-fruto', 'Perforación de fruto', 'Fruto', 'Orificios en el fruto con excremento o larvas en el interior de la pulpa.'],
        ['mosca-de-la-fruta', 'Mosca de la fruta', 'Fruto', 'Picaduras de oviposición, larvas blancas en la pulpa y frutos que se pudren y caen.'],
        ['pudricion-de-mazorca', 'Pudrición de mazorca', 'Fruto', 'Granos pardos con moho algodonoso o rosado y mazorcas ligeras.'],

        // Flor
        ['caida-de-flores', 'Caída de flores', 'Flor', 'Abscisión de flores abiertas o botones sin cuajar, con pedúnculos amarillentos.'],
        ['aborto-floral', 'Aborto floral', 'Flor', 'Botones que se detienen y caen antes de abrir, por estrés térmico o nutricional.'],
        ['pudricion-flor', 'Pudrición floral', 'Flor', 'Flores con pétalos pardos, moho y tejido blando, que puede avanzar al fruto.'],
        ['falta-de-cuajado', 'Falta de cuajado', 'Flor', 'Pocas flores se convierten en fruto; los frutos pequeños amarillean y caen.'],

        // Planta
        ['marchitez', 'Marchitez', 'Planta', 'Pérdida de turgencia durante el día, con recuperación nocturna al inicio del cuadro.'],
        ['marchitez-vascular', 'Marchitez vascular', 'Planta', 'Marchitez irreversible, con vasos oscuros o manchados al corte del tallo.'],
        ['achaparramiento', 'Achaparramiento', 'Planta', 'Plantas de porte reducido, entrenudos cortos y hojas agrupadas en roseta.'],
        ['muerte-regresiva', 'Muerte regresiva', 'Planta', 'Ramas que mueren desde la punta hacia la base, con tejido seco y hojas que no caen.'],
        ['vuelco', 'Vuelco', 'Planta', 'Plantas caídas por debilidad del cuello o de la raíz, a menudo con pudrición basal.'],
        ['ahilamiento', 'Ahilamiento', 'Planta', 'Tallos largos, delgados y pálidos por falta de luz o densidad excesiva.'],
        ['helada', 'Daño por helada', 'Planta', 'Hojas y brotes con aspecto cocido, pardo oscuro, después de temperaturas bajo cero.'],
        ['granizo', 'Daño por granizo', 'Planta', 'Heridas, perforaciones y desgarres mecánicos en hojas, tallos y frutos.'],
        ['viento', 'Daño por viento', 'Planta', 'Hojas rasgadas, tallos inclinados y abrasión de la cutícula en el lado expuesto.'],
        ['estres-hidrico', 'Estrés hídrico', 'Planta', 'Hojas enrolladas o grises, pérdida de turgencia y cierre estomático en horas de calor.'],
        ['muerte-de-plantulas', 'Muerte de plántulas', 'Planta', 'Plántulas que colapsan en el cuello tras la emergencia, con lesión acuosa.'],
        ['quemadura-solar', 'Quemadura solar', 'Planta', 'Manchas blanquecinas o pardas en tejido expuesto al sol, con deshidratación local.'],

        // Suelo
        ['salinidad', 'Salinidad', 'Suelo', 'Costra blanca en la superficie, bordes quemados y plantas pequeñas en manchones.'],
        ['compactacion', 'Compactación', 'Suelo', 'Suelo duro con capa endurecida, mal drenaje y raíces aplanadas o poco profundas.'],
        ['ph-extremo', 'pH extremo', 'Suelo', 'Suelo muy ácido o alcalino, con clorosis, bloqueo de nutrimentos y escaso desarrollo.'],
        ['exceso-de-agua', 'Exceso de agua', 'Suelo', 'Suelo saturado con encharcamientos, mal olor y raíces pardas.'],
        ['erosion', 'Erosión', 'Suelo', 'Pérdida de suelo superficial, surcos y raíces expuestas en pendientes.'],
        ['costra-superficial', 'Costra superficial', 'Suelo', 'Capa sellada y agrietada en la superficie que impide la emergencia.'],
        ['competencia-de-malezas', 'Competencia de malezas', 'Suelo', 'Arvenses que cubren el suelo y compiten por luz, agua y nutrimentos.'],
    ],
];
