SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS civilipedia;
CREATE DATABASE civilipedia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE civilipedia;

-- =====================
-- TABLES
-- =====================

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    avatar VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE article (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    image VARCHAR(255) NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    firstAuthor INT NOT NULL,
    user_id INT NOT NULL,
    FOREIGN KEY (firstAuthor) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE article_version (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    image_url TEXT NULL,
    user_id INT NOT NULL,
    article_id INT NOT NULL,
    FOREIGN KEY (article_id) REFERENCES article(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE ban (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reason TEXT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    user_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE contact (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE refresh_token (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(512) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================
-- UTILISATEURS
-- Mots de passe (Argon2id) — regenerer avec :
--   php -r "echo password_hash('Admin1234!', PASSWORD_ARGON2ID);"
--   php -r "echo password_hash('User1234!',  PASSWORD_ARGON2ID);"
-- =====================

INSERT INTO users (username, password, role, avatar) VALUES
('admin', '$argon2id$v=19$m=65536,t=4,p=1$VGZWT2lMZEk5V0xxL0I2TQ$df/23wLxuJEIcARr9+DW0grOvXtt0S5WTummO5r05FU', 'admin', '/private/avatars/admin.jpg'),
('jean_dupont', '$argon2id$v=19$m=65536,t=4,p=1$azNyRVk0T1dPOTAuMTNNNQ$1J/q/2xvOqqOzxthmXBJwf7j78j89QD27h5ndOYNj1g', 'user', '/private/avatars/jean_dupont.jpg'),
('marie_martin', '$argon2id$v=19$m=65536,t=4,p=1$aEQvaUhUSmVHdzYzL2UuZA$pvpwAe3QRQBGc6VaXVHK59uTFlOueggw9X+CRTwjdX0', 'user', '/private/avatars/marie_martin.jpg'),
('pierre_duval', '$argon2id$v=19$m=65536,t=4,p=1$Lm1mSVlpcjdtQVdTUDByWQ$0gWfGX6RkXfkJHN4FiXVUhdOs7zb32Dbi2hXnQwfwlc', 'user', '/private/avatars/pierre_duval.jpg');

-- =====================
-- ARTICLES (17 civilisations) — etat final (version courante)
-- =====================

INSERT INTO article (title, content, image, created_at, updated_at, firstAuthor, user_id) VALUES

('Les Mayas',
'<h2>Les Mayas — Batisseurs du temps</h2>
<p>La civilisation maya est l''une des plus fascinantes de l''Antiquite. Etablie dans le sud du Mexique, au Guatemala, au Belize, au Honduras et au Salvador, elle a prospere pendant plus de trois millenaires, entre <strong>2000 av. J.-C. et le XVIe siecle ap. J.-C.</strong>.</p>
<h3>Une ecriture et des mathematiques avancees</h3>
<p>Les Mayas ont developpe l''un des rares systemes d''ecriture entierement dechiffres de Mesoamerique : les <strong>glyphes mayas</strong>. Ils ont invente le concept du <strong>zero</strong> de maniere independante et leur systeme numerique en base 20 leur a permis des calculs astronomiques d''une precision stupefiante.</p>
<h3>Calendriers et astronomie</h3>
<p>Le <strong>Tzolkin</strong> (260 jours) et le <strong>Haab</strong> (365 jours) formaient ensemble le « Compte long ». Les astronomes mayas predisaient les eclipses et calculaient les cycles de Venus avec une precision que l''Europe n''atteindra que bien plus tard.</p>
<h3>Cites monumentales</h3>
<p>Des cites comme <strong>Tikal</strong>, <strong>Palenque</strong> et <strong>Chichen Itza</strong> temoignent d''une architecture sophistiquee : pyramides a degres, observatoires, terrains de jeu de balle. Le fameux <em>Castillo</em> de Chichen Itza projette a chaque equinoxe l''ombre d''un serpent descendant les marches — un effet intentionnel calcule des siecles a l''avance.</p>
<h3>Societe et heritage</h3>
<p>Contrairement a la legende, les Mayas ne sont pas une civilisation disparue : leurs descendants, environ <strong>7 millions de personnes</strong>, peuplent encore aujourd''hui toute la Mesoamerique, gardiens d''une culture vivante.</p>',
'mayas.jpg',
'2024-09-15 10:00:00', '2024-11-03 16:45:00', 2, 3),

('Les Vikings',
'<h2>Les Vikings — Explorateurs des mers du Nord</h2>
<p>Les Vikings etaient avant tout des <strong>marins, marchands et explorateurs</strong> originaires de Scandinavie, actifs entre le <strong>VIIIe et le XIe siecle</strong>. L''image du guerrier a cornes est largement une invention du XIXe siecle.</p>
<h3>Des navigateurs hors pair</h3>
<p>Les <strong>drakkars</strong>, a fond plat, naviguaient aussi bien en haute mer que sur les fleuves peu profonds d''Europe de l''Est. Guides par les etoiles et la <em>pierre solaire</em>, les Vikings atteignirent <strong>l''Islande, le Groenland et l''Amerique du Nord</strong> (Vinland) — cinq siecles avant Colomb.</p>
<h3>Commerce et fondations</h3>
<p>Les Vikings fonderent <strong>Dublin</strong>, <strong>Kiev</strong> et coloniserent la Normandie, dont les ducs conquerront l''Angleterre en 1066.</p>
<h3>Mythologie et organisation sociale</h3>
<p>La cosmologie norse articule <strong>Yggdrasil</strong>, <strong>Odin</strong> le borgne et <strong>Thor</strong> le tonnerre. Les femmes vikings jouissaient de droits remarquables : droit au divorce, a l''heritage, a la gestion du domaine. Les sagas islandaises nous en ont transmis la memoire avec une fidelite quasi historique.</p>',
'vikings.jpg',
'2024-09-16 11:00:00', '2024-10-28 09:15:00', 3, 2),

('Les Mongols',
'<h2>Les Mongols — L''Empire qui embrassa le monde</h2>
<p>Au debut du XIIIe siecle, <em>Temujin</em> unifia les tribus nomades des steppes et forgea le <strong>plus grand empire contigu de l''histoire</strong> sous le titre de <strong>Gengis Khan</strong>.</p>
<h3>La machine de guerre mongole</h3>
<p>Leurs armees combinaient mobilite extreme, discipline de fer et tactiques revolutionnaires : feintes de retraite calculees, ingenieurs de siege chinois, renseignement en profondeur. En soixante ans, ils soumirent la Chine, la Perse, la Russie et frapperent aux portes de l''Europe.</p>
<h3>La Pax Mongolica</h3>
<p>La Route de la Soie securisee permit a <strong>Marco Polo</strong> de traverser l''Asie. Idees, techniques et maladies cirulerent d''est en ouest — c''est par cette route que la <strong>peste noire</strong> atteignit l''Europe en 1347.</p>
<h3>Kublai Khan et la Chine</h3>
<p><strong>Kublai Khan</strong> conquit la Chine des Song et fonda la <strong>dynastie Yuan</strong>. Sa capitale Khanbalik (Pekin) eblouit Marco Polo : vastes palais, marches gigantesques, postes de relais tous les 40 km.</p>
<h3>Heritage ambigu</h3>
<p>La destruction de Bagdad (1258) mit fin au califat abbasside. Mais l''empire promut la tolerance religieuse, codifia le droit et crea un reseau intercontinental de communication sans precedent.</p>',
'mongols.jpg',
'2024-09-17 14:00:00', '2024-11-10 11:20:00', 2, 4),

('L''Egypte Ancienne',
'<h2>L''Egypte Ancienne — Eternite sur les rives du Nil</h2>
<p>Unifiee vers <strong>3100 av. J.-C.</strong> sous le legendaire pharaon Narmer, l''Egypte antique maintint une coherence culturelle extraordinaire pendant plus de trois mille ans.</p>
<h3>Le Nil, artere de vie</h3>
<p>La crue annuelle deposait un limon fertile sur les rives, transformant le desert en grenier. Cette regularite nourrit une vision du monde cyclique : la mort n''est qu''un passage, et l''ordre cosmique (<em>Maat</em>) doit etre perpetuellement maintenu par le pharaon, fils de Re sur terre.</p>
<h3>Pyramides et au-dela</h3>
<p>La <strong>Grande Pyramide de Kheops</strong> (vers 2560 av. J.-C.) est la seule des Sept Merveilles encore debout. Ses quatre faces s''alignent a moins de 0,05 degre des points cardinaux. Elle fut le batiment le plus haut du monde pendant <strong>3 800 ans</strong>.</p>
<h3>Hieroglyphes et savoirs</h3>
<p>Dechiffree en 1822 par <strong>Champollion</strong> grace a la Pierre de Rosette, l''ecriture hieroglyphique combinait logogrammes et phonogrammes. Les Egyptiens maitrisaient la geometrie, la medecine et une astronomie suffisamment precise pour orienter les pyramides.</p>
<h3>Pharaons remarquables</h3>
<p><strong>Hatshepsout</strong>, la femme-pharaon, regna vingt ans. <strong>Akhenaton</strong> imposa brievement le monotheisme solaire. <strong>Ramses II</strong> batit Abou Simbel. <strong>Toutankhamon</strong>, mort a 19 ans, nous a laisse le tresor funeraire le mieux conserve de l''Antiquite.</p>',
'egypte.jpg',
'2024-09-18 09:30:00', '2024-10-15 14:00:00', 3, 3),

('L''Empire Romain',
'<h2>L''Empire Romain — De la cite aux confins du monde</h2>
<p>Nee d''une modeste cite sur les bords du Tibre, Rome devint une Republique conquerante puis un Empire colossal s''etendant de la Mesopotamie a l''Ecosse. Son heritage juridique, linguistique et architectural structure encore le monde occidental.</p>
<h3>La Republique et ses institutions</h3>
<p>De 509 a 27 av. J.-C., deux consuls elus annuellement gouvernaient avec le Senat. Ce mecanisme d''equilibre des pouvoirs inspira les constituants americains et francais. Les guerres puniques revelerent la capacite d''adaptation de Rome face a <strong>Hannibal</strong> et ses elephants traversant les Alpes.</p>
<h3>Auguste et la Pax Romana</h3>
<p>Apres l''assassinat de <strong>Jules Cesar</strong> (44 av. J.-C.), <strong>Auguste</strong> instaura le Principat. S''ensuivirent deux siecles de paix relative durant lesquels les arts, le commerce et l''urbanisme prospererent.</p>
<h3>Droit, langue, genie civil</h3>
<p>Le droit romain fonde la plupart des systemes juridiques europeens. Le latin enfanta le francais, l''espagnol, l''italien et le portugais. Les Romains batirent <strong>80 000 km de routes</strong>, des aqueducs et des amphitheatres.</p>
<h3>Chute et metamorphose</h3>
<p>En 476, Romulus Augustule fut depose. Byzance surveecut jusqu''en 1453, et l''Eglise catholique perpetua la langue latine et le droit romain a travers tout le Moyen Age.</p>',
'romains.jpg',
'2024-09-19 08:00:00', '2024-11-20 10:30:00', 4, 2),

('La Grece Antique',
'<h2>La Grece Antique — Le berceau de la pensee occidentale</h2>
<p>La Grece antique etait un archipel de <strong>cites-Etats</strong> — Athenes, Sparte, Corinthe, Thebes — qui partageaient une langue, des dieux et un sentiment d''identite culturelle commune.</p>
<h3>La democratie athenienne</h3>
<p>Athenes inventa la <strong>democratie directe</strong> sous Clisthene (508 av. J.-C.) et la perfectionna sous Pericles. L''Agora, place publique au coeur de la cite, etait le lieu de tous les debats citoyens.</p>
<h3>Philosophie : le triomphe de la raison</h3>
<p><strong>Socrate</strong>, condamne a mort en 399 av. J.-C., devint le martyr de la liberte de penser. <strong>Platon</strong> theorisa la Republique ideale. <strong>Aristote</strong>, precepteur d''Alexandre le Grand, jeta les bases de la logique, de la biologie et de l''ethique.</p>
<h3>Sciences et mathematiques</h3>
<p><strong>Euclide</strong> codifia la geometrie. <strong>Archimede</strong> formula le principe de la poussee. <strong>Eratosthene</strong> mesura la circonference de la Terre a 250 av. J.-C. avec une erreur de seulement 2 %.</p>
<h3>Arts et mythologie</h3>
<p>Le Parthenon, les tragedies de Sophocle, les epopees d''<strong>Homere</strong> continuent de nourrir la litterature et le cinema contemporains. Les dieux grecs peuplent encore l''imaginaire collectif mondial deux mille cinq cents ans apres leur creation.</p>',
'grece.jpg',
'2024-09-20 10:00:00', '2024-10-31 08:45:00', 2, 4),

('Les Azteques',
'<h2>Les Azteques — Seigneurs du Soleil</h2>
<p>Sur une ile du lac Texcoco, <strong>Tenochtitlan</strong> s''elevait au XVe siecle comme l''une des plus grandes villes du monde — 200 000 habitants, plus que Londres a la meme epoque.</p>
<h3>Fondation et Triple Alliance</h3>
<p>Guides par la prophetie d''un aigle devorant un serpent sur un cactus, les Mexicas fonderent Tenochtitlan en 1325. Allies avec Texcoco et Tlacopan (1428), ils formerent la <strong>Triple Alliance</strong> qui domina tout le Mexique central.</p>
<h3>Religion et sacrifice</h3>
<p>Le dieu solaire <strong>Huitzilopochtli</strong> exigeait du sang pour faire lever le soleil. Les sacrifices etaient a la fois rite religieux et instrument de domination politique sur les peuples vassaux.</p>
<h3>Agriculture et ingenierie hydraulique</h3>
<p>Les <strong>chinampas</strong> — jardins flottants construits par couches successives — permettaient plusieurs recoltes annuelles. Un reseau de digues et de canaux sophistique regulait les eaux du lac.</p>
<h3>La Conquete (1519-1521)</h3>
<p><strong>Hernan Cortes</strong> exploita les rancoeurs des peuples vassaux et une epidemie devastatrice de variole. En 1521, Tenochtitlan tombait apres 80 jours de siege. Sur ses ruines fut batied <strong>Mexico</strong>.</p>',
'azteques.jpg',
'2024-09-21 09:00:00', '2024-11-05 15:10:00', 3, 2),

('L''Empire Inca',
'<h2>L''Empire Inca — Seigneurs des Andes</h2>
<p>Le <em>Tawantinsuyu</em> etait au XVe siecle le plus grand empire d''Amerique, fort de <strong>10 a 12 millions d''habitants</strong> repartis sur 4 000 km de cordillere, de deserts cotiers et de forets amazoniennes.</p>
<h3>L''Inca fils du Soleil</h3>
<p>Le souverain etait la reincarnation d''<strong>Inti</strong>, le dieu solaire. La capitale <strong>Cusco</strong> etait ornee de plaques d''or. Le <em>Coricancha</em>, temple du Soleil, eblouissait les visiteurs.</p>
<h3>Machu Picchu et l''architecture sismique</h3>
<p>Construite vers 1450 a 2 430 m d''altitude, <strong>Machu Picchu</strong> reste l''un des sites archeologiques les plus epoustouflants du monde. Les blocs de pierre, tailles avec une precision millimetrique, resistent aux seismes depuis cinq siecles.</p>
<h3>Routes, quipus et chasquis</h3>
<p>Plus de <strong>40 000 km de routes</strong> sillonnaient l''empire. Les <em>chasquis</em> en relais couvraient 400 km par jour. Les <strong>quipus</strong> — cordelettes nouees de differentes couleurs — encodaient statistiques et calendriers.</p>
<h3>La chute sous Pizarro</h3>
<p>En 1532, <strong>Francisco Pizarro</strong> captura l''Inca <strong>Atahualpa</strong> et le fit executer malgre une rancon d''une chambre entiere remplie d''or.</p>',
'incas.jpg',
'2024-09-22 10:30:00', '2024-10-22 13:00:00', 4, 3),

('La Chine Imperiale',
'<h2>La Chine Imperiale — Quatre mille ans de continuite</h2>
<p>Des dynasties <strong>Shang</strong> (vers 1600 av. J.-C.) jusqu''a la chute des <strong>Qing</strong> en 1912, la Chine connue une continuite culturelle et institutionnelle sans equivalent.</p>
<h3>Les grandes dynasties</h3>
<p><strong>Qin Shi Huangdi</strong> (221 av. J.-C.) unifia la Chine et initia la <strong>Grande Muraille</strong>. Les <strong>Han</strong> ouvrirent la Route de la Soie. Les <strong>Tang</strong> porterent la poesie et les arts a leur apogee. Les <strong>Song</strong> developperent l''economie monetaire et l''imprimerie.</p>
<h3>Inventions qui changerent le monde</h3>
<p>La Chine nous a donne l''<strong>imprimerie a caracteres mobiles</strong> (Bi Sheng, XIe siecle), la <strong>poudre a canon</strong>, le <strong>papier</strong>, la <strong>boussole</strong> et la porcelaine. Ces inventions atteignirent l''Europe via la Route de la Soie.</p>
<h3>Confucius et les examens imperiaux</h3>
<p>La pensee de <strong>Confucius</strong> structure encore la societe chinoise : respect de la hierarchie, piete filiale, culte de l''education. Le systeme des examens imperiaux permettait a tout homme lettre d''acceder a la haute administration.</p>
<h3>La Route de la Soie</h3>
<p>Ce reseau commercial de 7 000 km reliait Chang''an (Xi''an) a Rome, transportant soie, epices, mais aussi bouddhisme, islam et idees nouvelles.</p>',
'chine.jpg',
'2024-09-23 11:00:00', '2024-11-15 09:00:00', 2, 4),

('La Mesopotamie',
'<h2>La Mesopotamie — Le pays entre les fleuves</h2>
<p>Entre le Tigre et l''Euphrate, dans l''actuel Irak, est nee vers <strong>3500 av. J.-C.</strong> la premiere civilisation urbaine de l''histoire. Les Sumeriens inventerent l''ecriture, la roue et les premieres lois ecrites.</p>
<h3>L''ecriture cuneiforme</h3>
<p>Nee comme systeme comptable — pictogrammes sur tablettes d''argile pour enregistrer cereales et betail —, la <strong>cuneiforme</strong> evolua en ecriture syllabique utilisee pendant 3 000 ans dans une douzaine de langues.</p>
<h3>L''Epopee de Gilgamesh</h3>
<p>Composee vers 2100 av. J.-C., c''est la plus ancienne oeuvre litteraire connue. Elle contient un recit de deluge — avec arche, animaux en couple et colombe — frappant de ressemblance avec la Genese.</p>
<h3>Hammurabi et le droit</h3>
<p>Les 282 lois du code d''<strong>Hammurabi</strong> (1792-1750 av. J.-C.) graves sur stele regissent salaires, divorces et punitions. C''est l''un des premiers codes juridiques de l''histoire.</p>
<h3>Science babylonienne</h3>
<p>Les astronomes babyloniens developperent le zodiaque et predit les eclipses. Leur systeme numerique en <strong>base 60</strong> nous a leguele les 60 secondes par minute et les 360 degres du cercle.</p>',
'mesopotamie.jpg',
'2024-09-24 14:00:00', '2024-10-18 17:30:00', 3, 2),

('L''Empire Perse',
'<h2>L''Empire Perse — La plus grande puissance du monde antique</h2>
<p>En une generation, <strong>Cyrus II le Grand</strong> (559-530 av. J.-C.) batit un empire s''etendant de l''Egee a l''Indus — reunissant sous un meme sceptre des dizaines de peuples, de langues et de religions.</p>
<h3>Cyrus et la tolerance</h3>
<p>Le <em>Cylindre de Cyrus</em> (539 av. J.-C.) temoigne d''une politique inedite : Cyrus libera les peuples deportes par Babylone et respecta leurs cultes. Les Hebreux en exil le celebrerent comme un messie.</p>
<h3>Persepolis</h3>
<p>Fondee par <strong>Darius Ier</strong>, <strong>Persepolis</strong> etait la vitrine monumentale de l''empire. Ses bas-reliefs montrent les delegations de vingt-trois nations apportant tribut. Alexandre le Grand la brula en 330 av. J.-C.</p>
<h3>Route Royale et logistique imperiale</h3>
<p>La <strong>Route Royale</strong> (2 700 km) permettait de transmettre un message en sept jours grace a un reseau de relais equestres — ancetre direct de la poste romaine.</p>
<h3>Zoroastre et l''heritage religieux</h3>
<p>Le dualisme cosmique de <strong>Zoroastre</strong> influenca profondement le judaisme tardif, le christianisme et l''islam. Les concepts de paradis, d''enfer et de jugement dernier leur doivent beaucoup.</p>',
'perses.jpg',
'2024-09-25 09:00:00', '2024-11-08 11:00:00', 4, 3),

('Les Pheniciens',
'<h2>Les Pheniciens — Marchands de l''horizon</h2>
<p>Installes sur une etroite bande cotiere du Liban actuel, les Pheniciens exercerent une influence disproportionnee par le commerce, la navigation et un cadeau inestimable : <strong>l''alphabet</strong>.</p>
<h3>L''alphabet, heritage universel</h3>
<p>Vers 1050 av. J.-C., les Pheniciens simplifierent les ecritures du Proche-Orient en un alphabet de <strong>22 consonnes</strong>. Les Grecs y ajouterent les voyelles, les Romains l''adapterent. Les lettres que vous lisez en ce moment descendent de cet alphabet.</p>
<h3>Maitres de la mer Mediterranee</h3>
<p>Premiers navigateurs a utiliser l''<strong>Etoile Polaire</strong> comme guide nocturne, les Pheniciens coloniserent tout le bassin mediterraneen. Vers 600 av. J.-C., une expedition fit probablement le <strong>tour de l''Afrique</strong> — 2 000 ans avant Vasco de Gama.</p>
<h3>Carthage et Hannibal</h3>
<p><strong>Carthage</strong>, fondee vers 814 av. J.-C., devint une puissance majeure. Son general <strong>Hannibal Barca</strong> traversa les Alpes avec ses elephants et infligea a Rome ses pires defaites.</p>
<h3>Le pourpre tyrien</h3>
<p>La teinture pourpre, extraite du murex, valait plusieurs fois son poids en argent — reservee aux rois et aux empereurs. C''est de la l''expression « ne dans la pourpre ».</p>',
'pheniciens.jpg',
'2024-09-26 10:00:00', '2024-10-25 15:45:00', 2, 4),

('Les Celtes',
'<h2>Les Celtes — Peuples de l''ame et du metal</h2>
<p>Entre le <strong>VIIIe siecle av. J.-C.</strong> et la conquete romaine, les cultures celtiques s''etendaient de l''Anatolie a l''Irlande — non pas un empire, mais une constellation de peuples lies par une langue commune et une religion partagee.</p>
<h3>Maitres du fer et de l''or</h3>
<p>Les Celtes maitrisaient la metallurgie du fer quand leurs voisins etaient encore au bronze. Leurs epees a longue lame, leurs casques ores et leurs torques d''or torsades temoignent d''un sens artistique elabore.</p>
<h3>Les druides</h3>
<p>Au sommet de la societe celtique, les <strong>druides</strong> etaient a la fois pretres, juges, philosophes et astronomes. Leur savoir, transmis oralement pendant vingt annees, portait sur la cosmologie, les plantes medicinales et la loi.</p>
<h3>Vercingetorix et Alesia</h3>
<p>En 52 av. J.-C., <strong>Vercingetorix</strong> souleva la Gaule entiere. La bataille d''Alesia scella la victoire de Cesar. Vercingetorix deposa ses armes aux pieds du vainqueur et fut etrangle a Rome six ans plus tard.</p>
<h3>Heritage vivant</h3>
<p>La culture celtique surveccut en Irlande, au Pays de Galles, en Bretagne. Les legendes arthuriennes, <strong>Halloween</strong> (issu de <em>Samhain</em>) et les langues gaeliques relient le monde contemporain a ces anciens peuples.</p>',
'celtes.jpg',
'2024-09-27 11:30:00', '2024-11-01 10:15:00', 3, 4),

('L''Empire Ottoman',
'<h2>L''Empire Ottoman — Six siecles entre Orient et Occident</h2>
<p>Fonde en 1299 par <strong>Osman Ier</strong>, l''Empire ottoman s''etendit a son apogee sur trois continents et dura <strong>six siecles</strong> — une longevite politique exceptionnelle dans l''histoire.</p>
<h3>La prise de Constantinople (1453)</h3>
<p>Le <strong>29 mai 1453</strong>, <strong>Mehmed II</strong> s''empara de Constantinople apres 53 jours de siege. La chute de la ville marque officiellement la fin du Moyen Age. Mehmed, polyglotte et lettre, se posa en heritier des empereurs romains.</p>
<h3>Soliman le Magnifique</h3>
<p>Sous <strong>Soliman Ier</strong> (1520-1566), l''empire atteignit son apogee. Son code juridique et sa tolerance envers les non-musulmans en firent une des civilisations les plus avancees du XVIe siecle.</p>
<h3>Le systeme du millet</h3>
<p>Chaque communaute religieuse (orthodoxes, juifs, armeniens) jouissait d''une autonomie juridique interne — un modele de coexistence qui permit a des millions de personnes de vivre cote a cote pendant des siecles.</p>
<h3>Declin et dissolution</h3>
<p>Le XIXe siecle vit l''empire se desagreger sous les poussees nationalistes. La Premiere Guerre mondiale sonna le glas. En 1923, <strong>Mustafa Kemal Ataturk</strong> proclama la Republique turque sur ses ruines.</p>',
'ottomans.jpg',
'2024-09-28 08:30:00', '2024-11-12 14:20:00', 4, 3),

('L''Age d''Or de l''Islam',
'<h2>L''Age d''Or de l''Islam — Lumieres d''Orient</h2>
<p>Du VIIIe au XIIIe siecle, le monde islamique vivait un formidable bouillonnement intellectuel : <strong>l''Age d''Or</strong>. De Bagdad a Cordoue, des savants traduisirent, commenterent et depasserent l''heritage grec et indien.</p>
<h3>La Maison de la Sagesse de Bagdad</h3>
<p>La <em>Bayt al-Hikma</em> fut le plus grand centre intellectuel du monde medieval. Savants arabes, persans et nestoriens y travaillaient cote a cote a traduire Aristote, Galien et Euclide, puis a les depasser.</p>
<h3>Mathematiques et sciences</h3>
<p><strong>Al-Khwarizmi</strong> inventa l''algebre (son nom a donne « algorithme »). <strong>Ibn al-Haytham</strong> fonda l''optique moderne. <strong>Ibn Sina</strong> (<em>Avicenne</em>) compila le <em>Canon de la medecine</em>, manuel des universites europeennes jusqu''au XVIIe siecle.</p>
<h3>Cordoue, capitale intellectuelle de l''Europe</h3>
<p>Au Xe siecle, <strong>Cordoue</strong> etait la plus grande ville d''Europe occidentale : 500 000 habitants et une bibliotheque de 400 000 volumes. Des erudits chretiens venaient y etudier les mathematiques et la medecine.</p>
<h3>Heritage dans notre quotidien</h3>
<p>Des centaines de mots nous viennent de l''arabe : <em>algebre</em>, <em>algorithme</em>, <em>alchimie</em>, <em>alcool</em>, <em>almanach</em>, <em>zenith</em>. Chaque nuit, nous regardons des etoiles aux noms arabes : Aldebaran, Betelgeuse, Rigel, Vega.</p>',
'islam.jpg',
'2024-09-29 09:00:00', '2024-10-30 16:00:00', 2, 3),

('Le Japon Feodal',
'<h2>Le Japon Feodal — L''age des samourais</h2>
<p>De la fin du XIIe siecle jusqu''a la restauration Meiji (1868), le Japon fut gouverne par des <strong>shoguns</strong> tandis que l''Empereur n''en gardait que le prestige sacre.</p>
<h3>Shoguns et samourais</h3>
<p>En 1185, <strong>Minamoto no Yoritomo</strong> etablit le premier gouvernement militaire (<em>bakufu</em>) a Kamakura. La classe des <strong>samourais</strong>, regie par le <em>bushido</em> (fidelite, courage, mort honorable), forma l''elite militaire du pays pendant sept siecles.</p>
<h3>Zen, the et arts</h3>
<p>Le bouddhisme <strong>zen</strong> impregnait profondement la culture samourai : meditation, discipline, quete de l''essentiel. La <em>ceremonie du the</em>, les jardins zen, le theatre no conjuguaient esthetique et philosophie.</p>
<h3>L''ere Edo et l''isolement volontaire</h3>
<p><strong>Tokugawa Ieyasu</strong> unifia le Japon en 1603. Son successeur ferma le pays aux etrangers (<em>sakoku</em>) en 1635. Cette isolation produisit une culture purement japonaise : <em>kabuki</em>, <em>ukiyo-e</em>, <em>haiku</em>.</p>
<h3>L''ouverture forcee et Meiji</h3>
<p>En 1853, les canonnieres du commodore <strong>Matthew Perry</strong> forcerent le Japon a s''ouvrir. La <strong>Restauration Meiji</strong> (1868) abolit le shogunat et lanca une modernisation fulgurante.</p>',
'japon.jpg',
'2024-09-30 10:00:00', '2024-11-18 09:30:00', 3, 2),

('L''Empire Maurya',
'<h2>L''Empire Maurya — L''Inde unie</h2>
<p>Au IVe siecle av. J.-C., <strong>Chandragupta Maurya</strong> renversa la dynastie Nanda et fonda le premier empire a unifier la majeure partie du sous-continent indien.</p>
<h3>Chandragupta et l''Arthashastra</h3>
<p>Conseille par le ministre <strong>Kautilya</strong> (Chanakya), auteur de l''<em>Arthashastra</em> — traite de science politique d''une lucidite machiavelique —, Chandragupta batit un Etat centralise avec une administration sophistiquee et une armee de 700 000 hommes.</p>
<h3>Ashoka, l''Empereur de la compassion</h3>
<p>Apres la sanglante bataille de Kalinga (260 av. J.-C., 100 000 morts), <strong>Ashoka</strong> se convertit au bouddhisme et entreprit de gouverner selon les principes du <em>dharma</em> : non-violence, respect de toutes les religions, construction d''hopitaux.</p>
<h3>Routes, commerce et universites</h3>
<p>L''empire entretenait des routes commerciales dans tout le sous-continent et des ambassades avec les royaumes hellenistiques. L''universite de <strong>Taxila</strong> attirait des etudiants de tout l''Asie.</p>
<h3>Un heritage visible aujourd''hui</h3>
<p>La <strong>roue du dharma</strong> tiree du chapiteau d''Ashoka orne le drapeau de la Republique de l''Inde — seul empire de l''Antiquite dont le symbole figure encore sur un drapeau souverain du XXIe siecle.</p>',
'maurya.jpg',
'2024-10-01 08:00:00', '2024-11-22 10:00:00', 4, 2);


-- =====================
-- VERSIONS D''ARTICLES
-- Convention images : <slug>.jpg
-- Placez vos images dans public du client
-- =====================

-- -----------------------------------------------------------
-- Article 1 : Les Mayas (3 versions — evolution visible)
-- -----------------------------------------------------------

-- v1 : ebauche initiale par jean_dupont (contenu minimal)
INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('Les Mayas',
'<h2>Les Mayas</h2>
<p>Civilisation mesoamericaine qui a prospere entre 2000 av. J.-C. et le XVIe siecle. Connus pour leurs pyramides, leur calendrier et leur systeme d''ecriture a glyphes.</p>
<p>Ils habitaient le sud du Mexique, le Guatemala, le Belize et le Honduras. Leur civilisation a connu un age classique entre 250 et 900 ap. J.-C., marque par la construction de grandes cites.</p>',
'2024-09-15 10:00:00', 'mayas.jpg', 2, 1);

-- v2 : enrichissement par marie_martin (section astronomie + mathematiques)
INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('Les Mayas — Batisseurs du temps',
'<h2>Les Mayas — Batisseurs du temps</h2>
<p>Civilisation mesoamericaine ayant prospere entre <strong>2000 av. J.-C. et le XVIe siecle</strong>, les Mayas sont connus pour leurs pyramides, leur calendrier sophistique et leur ecriture glyphique.</p>
<h3>Calendriers et astronomie</h3>
<p>Les Mayas utilisaient deux calendriers : le <strong>Tzolkin</strong> (260 jours, rituel) et le <strong>Haab</strong> (365 jours, solaire). Combines, ils formaient le « Compte long ». Leurs astronomes predisaient les eclipses et calculaient les cycles de Venus avec une precision remarquable.</p>
<h3>Ecriture et mathematiques</h3>
<p>Ils ont invente le concept du <strong>zero</strong> de maniere independante et developpe un systeme numerique en base 20. Leur ecriture glyphique etait gravee sur des steles, des temples et des codex en ecorce de figuier.</p>',
'2024-10-12 14:30:00', 'mayas.jpg', 3, 1);

-- v3 : version finale complete par marie_martin (ajout cites + heritage)
INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('Les Mayas — Batisseurs du temps',
(SELECT content FROM article WHERE id = 1),
'2024-11-03 16:45:00', 'mayas.jpg', 3, 1);

-- -----------------------------------------------------------
-- Article 2 : Les Vikings (3 versions)
-- -----------------------------------------------------------

-- v1 : creation par marie_martin
INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('Les Vikings',
'<h2>Les Vikings</h2>
<p>Peuple scandinave connu pour ses explorations maritimes du VIIIe au XIe siecle. Les Vikings naviguaient a bord de drakkars et ont commerce et pille a travers toute l''Europe.</p>',
'2024-09-16 11:00:00', 'vikings.jpg', 3, 2);

-- v2 : revision par jean_dupont (section navigation + organisation sociale)
INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('Les Vikings — Explorateurs des mers',
'<h2>Les Vikings — Explorateurs des mers</h2>
<p>Contrairement aux idees recues, les Vikings etaient avant tout des <strong>marchands et explorateurs</strong>. Leurs drakkars a fond plat leur permettaient de naviguer en haute mer comme sur les fleuves peu profonds.</p>
<h3>Navigation et decouvertes</h3>
<p>Les Vikings ont atteint l''Islande, le Groenland et l''Amerique du Nord (Vinland) — cinq siecles avant Christophe Colomb. Ils guidaient leurs navires grace aux etoiles et a la <em>pierre solaire</em>.</p>
<h3>Organisation sociale</h3>
<p>La societe viking etait divisee en <em>thralls</em> (esclaves), <em>karls</em> (hommes libres) et <em>jarls</em> (nobles). Les femmes pouvaient divorcer et heriter, ce qui etait exceptionnel pour l''epoque.</p>',
'2024-10-05 09:20:00', 'vikings.jpg', 2, 2);

-- v3 : version finale par jean_dupont
INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('Les Vikings — Explorateurs des mers du Nord',
(SELECT content FROM article WHERE id = 2),
'2024-10-28 09:15:00', 'vikings.jpg', 2, 2);

-- -----------------------------------------------------------
-- Article 3 : Les Mongols (2 versions)
-- -----------------------------------------------------------

INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('Les Mongols',
'<h2>Les Mongols</h2>
<p>Empire fonde par Gengis Khan au XIIIe siecle, le plus grand empire terrestre de l''histoire. Les Mongols ont conquis la Chine, la Perse, la Russie et frappe aux portes de l''Europe.</p>',
'2024-09-17 14:00:00', 'mongols.jpg', 2, 3);

INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('Les Mongols — L''Empire qui embrassa le monde',
(SELECT content FROM article WHERE id = 3),
'2024-11-10 11:20:00', 'mongols.jpg', 4, 3);

-- -----------------------------------------------------------
-- Article 4 : Egypte (3 versions)
-- -----------------------------------------------------------

INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('L''Egypte Ancienne',
'<h2>L''Egypte Ancienne</h2>
<p>L''une des plus anciennes civilisations du monde, l''Egypte antique s''est developpee le long du Nil pendant plus de 3 000 ans. Connue pour ses pyramides, ses pharaons et ses hieroglyphes.</p>',
'2024-09-18 09:30:00', 'egypte.jpg', 3, 4);

INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('L''Egypte Ancienne — Eternite sur le Nil',
'<h2>L''Egypte Ancienne — Eternite sur le Nil</h2>
<p>Unifiee vers 3100 av. J.-C. sous le pharaon Narmer, l''Egypte maintint une coherence culturelle extraordinaire pendant trois millenaires.</p>
<h3>Les pyramides</h3>
<p>La <strong>Grande Pyramide de Kheops</strong> (vers 2560 av. J.-C.) est la seule des Sept Merveilles encore debout. Elle fut le batiment le plus haut du monde pendant 3 800 ans. Ses quatre faces s''alignent a moins de 0,05 degre des points cardinaux.</p>
<h3>Hieroglyphes</h3>
<p>L''ecriture hieroglyphique fut dechiffree en 1822 par <strong>Champollion</strong> grace a la Pierre de Rosette. Elle combinait logogrammes et phonogrammes sur une periode de 3 500 ans d''utilisation continue.</p>',
'2024-10-02 11:00:00', 'egypte.jpg', 3, 4);

INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('L''Egypte Ancienne — Eternite sur les rives du Nil',
(SELECT content FROM article WHERE id = 4),
'2024-10-15 14:00:00', 'egypte.jpg', 3, 4);

-- -----------------------------------------------------------
-- Articles 5 a 17 : version initiale courte + version finale
-- -----------------------------------------------------------

INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('L''Empire Romain',
'<h2>L''Empire Romain</h2><p>Rome, fondee selon la tradition en 753 av. J.-C., devint l''une des plus grandes puissances de l''Antiquite. Son empire s''etendit de la Bretagne a la Mesopotamie, laissant un heritage juridique et linguistique incomparable.</p>',
'2024-09-19 08:00:00', 'romains.jpg', 4, 5);

INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('L''Empire Romain — De la cite aux confins du monde',
(SELECT content FROM article WHERE id = 5),
'2024-11-20 10:30:00', 'romains.jpg', 2, 5);

INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('La Grece Antique',
'<h2>La Grece Antique</h2><p>Berceau de la democratie, de la philosophie et des sciences occidentales, la Grece antique reunissait des cites-Etats comme Athenes et Sparte entre le VIIIe et le IIe siecle av. J.-C.</p>',
'2024-09-20 10:00:00', 'grece.jpg', 2, 6);

INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('La Grece Antique — Le berceau de la pensee occidentale',
(SELECT content FROM article WHERE id = 6),
'2024-10-31 08:45:00', 'grece.jpg', 4, 6);

INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('Les Azteques',
'<h2>Les Azteques</h2><p>Civilisation mesoamericaine ayant domine le Mexique central du XIVe au XVIe siecle. Les Azteques ont bati Tenochtitlan, l''une des plus grandes cites du monde a leur epoque, et developpe une agriculture hydraulique ingenieuse.</p>',
'2024-09-21 09:00:00', 'azteques.jpg', 3, 7);

INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('Les Azteques — Seigneurs du Soleil',
(SELECT content FROM article WHERE id = 7),
'2024-11-05 15:10:00', 'azteques.jpg', 2, 7);

INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('L''Empire Inca',
'<h2>L''Empire Inca</h2><p>Le plus grand empire d''Amerique precolombienne, s''etendant sur la cote ouest de l''Amerique du Sud. Les Incas sont connus pour Machu Picchu, leurs 40 000 km de routes et leurs quipus, systeme de comptabilite par cordelettes.</p>',
'2024-09-22 10:30:00', 'incas.jpg', 4, 8);

INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('L''Empire Inca — Seigneurs des Andes',
(SELECT content FROM article WHERE id = 8),
'2024-10-22 13:00:00', 'incas.jpg', 3, 8);

INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('La Chine Imperiale',
'<h2>La Chine Imperiale</h2><p>L''une des plus anciennes civilisations continues du monde. La Chine imperiale a invente la poudre a canon, la boussole, le papier et l''imprimerie, et maintenu une continuite culturelle de 3 500 ans.</p>',
'2024-09-23 11:00:00', 'chine.jpg', 2, 9);

INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('La Chine Imperiale — Quatre mille ans de continuite',
(SELECT content FROM article WHERE id = 9),
'2024-11-15 09:00:00', 'chine.jpg', 4, 9);

INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('La Mesopotamie',
'<h2>La Mesopotamie</h2><p>Berceau de la civilisation, la Mesopotamie (actuel Irak) a vu naitre l''ecriture cuneiforme, les premieres cites et les premiers codes de lois. L''Epopee de Gilgamesh y fut composee 2 000 ans avant la Bible.</p>',
'2024-09-24 14:00:00', 'mesopotamie.jpg', 3, 10);

INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('La Mesopotamie — Le pays entre les fleuves',
(SELECT content FROM article WHERE id = 10),
'2024-10-18 17:30:00', 'mesopotamie.jpg', 2, 10);

INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('L''Empire Perse',
'<h2>L''Empire Perse</h2><p>Fonde par Cyrus le Grand au VIe siecle av. J.-C., l''Empire achemenide fut le plus grand empire du monde antique, reunissant sous un meme sceptre une cinquantaine de peuples differents.</p>',
'2024-09-25 09:00:00', 'perses.jpg', 4, 11);

INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('L''Empire Perse — La plus grande puissance du monde antique',
(SELECT content FROM article WHERE id = 11),
'2024-11-08 11:00:00', 'perses.jpg', 3, 11);

INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('Les Pheniciens',
'<h2>Les Pheniciens</h2><p>Peuple de marins et de marchands du Liban actuel, les Pheniciens ont cree l''alphabet dont descendent tous les systemes d''ecriture alphabetiques modernes, et fonde Carthage.</p>',
'2024-09-26 10:00:00', 'pheniciens.jpg', 2, 12);

INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('Les Pheniciens — Marchands de l''horizon',
(SELECT content FROM article WHERE id = 12),
'2024-10-25 15:45:00', 'pheniciens.jpg', 4, 12);

INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('Les Celtes',
'<h2>Les Celtes</h2><p>Ensemble de peuples indo-europeens qui s''etendaient de l''Irlande a l''Anatolie entre le VIIIe siecle av. J.-C. et la conquete romaine. Connus pour leur art, leurs druides et la resistance de Vercingetorix.</p>',
'2024-09-27 11:30:00', 'celtes.jpg', 3, 13);

INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('Les Celtes — Peuples de l''ame et du metal',
(SELECT content FROM article WHERE id = 13),
'2024-11-01 10:15:00', 'celtes.jpg', 4, 13);

INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('L''Empire Ottoman',
'<h2>L''Empire Ottoman</h2><p>Fonde en 1299, l''Empire ottoman domina l''Europe du Sud-Est, le Moyen-Orient et l''Afrique du Nord pendant six siecles avant de s''effondrer apres la Premiere Guerre mondiale.</p>',
'2024-09-28 08:30:00', 'ottomans.jpg', 4, 14);

INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('L''Empire Ottoman — Six siecles entre Orient et Occident',
(SELECT content FROM article WHERE id = 14),
'2024-11-12 14:20:00', 'ottomans.jpg', 3, 14);

INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('L''Age d''Or de l''Islam',
'<h2>L''Age d''Or de l''Islam</h2><p>Du VIIIe au XIIIe siecle, le monde islamique connut un essor scientifique et culturel sans precedent. Bagdad et Cordoue etaient les capitales mondiales du savoir, quand l''Europe cherchait encore ses reperes.</p>',
'2024-09-29 09:00:00', 'islam.jpg', 2, 15);

INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('L''Age d''Or de l''Islam — Lumieres d''Orient',
(SELECT content FROM article WHERE id = 15),
'2024-10-30 16:00:00', 'islam.jpg', 3, 15);

INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('Le Japon Feodal',
'<h2>Le Japon Feodal</h2><p>De 1185 a 1868, le Japon fut gouverne par des shoguns militaires tandis que les samourais constituaient la classe guerriere. Cette periode vit l''essor du zen, du kabuki et d''une culture raffinee et originale.</p>',
'2024-09-30 10:00:00', 'japon.jpg', 3, 16);

INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('Le Japon Feodal — L''age des samourais',
(SELECT content FROM article WHERE id = 16),
'2024-11-18 09:30:00', 'japon.jpg', 2, 16);

INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('L''Empire Maurya',
'<h2>L''Empire Maurya</h2><p>Premier empire a unifier l''Inde, fonde par Chandragupta Maurya au IVe siecle av. J.-C. L''Empereur Ashoka, son petit-fils, est l''une des figures les plus respectees de l''histoire asiatique.</p>',
'2024-10-01 08:00:00', 'maurya.jpg', 4, 17);

INSERT INTO article_version (title, content, created_at, image_url, user_id, article_id) VALUES
('L''Empire Maurya — L''Inde unie',
(SELECT content FROM article WHERE id = 17),
'2024-11-22 10:00:00', 'maurya.jpg', 2, 17);

SET FOREIGN_KEY_CHECKS = 1;