from pathlib import Path
from datetime import date

from reportlab.lib import colors
from reportlab.lib.colors import HexColor
from reportlab.lib.enums import TA_CENTER, TA_LEFT
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import mm
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.platypus import (
    BaseDocTemplate, Frame, PageTemplate, Paragraph, Spacer, PageBreak,
    Table, TableStyle, Image, KeepTogether, Flowable, HRFlowable,
)

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "output" / "pdf" / "presentation-plateforme-dgcpt-multi-niveaux.pdf"
LOGO = ROOT / "public" / "assets" / "branding" / "dgcpt-logo.png"

NAVY = HexColor("#061020")
BLUE = HexColor("#0A2A66")
CYAN = HexColor("#00BFEA")
GREEN = HexColor("#00A86B")
YELLOW = HexColor("#E9C600")
INK = HexColor("#122033")
MUTED = HexColor("#5C6F84")
PALE = HexColor("#EDF5FA")
LINE = HexColor("#D5E2EC")
WHITE = colors.white


def register_fonts():
    font_dir = Path("C:/Windows/Fonts")
    regular = font_dir / "arial.ttf"
    bold = font_dir / "arialbd.ttf"
    if regular.exists() and bold.exists():
        pdfmetrics.registerFont(TTFont("Dgcpt", str(regular)))
        pdfmetrics.registerFont(TTFont("Dgcpt-Bold", str(bold)))
        return "Dgcpt", "Dgcpt-Bold"
    return "Helvetica", "Helvetica-Bold"


FONT, FONT_BOLD = register_fonts()

styles = getSampleStyleSheet()
styles.add(ParagraphStyle(
    name="Kicker", fontName=FONT_BOLD, fontSize=8.5, leading=11,
    textColor=CYAN, spaceAfter=4, tracking=1.1,
))
styles.add(ParagraphStyle(
    name="TitleD", fontName=FONT_BOLD, fontSize=23, leading=27,
    textColor=NAVY, spaceAfter=9,
))
styles.add(ParagraphStyle(
    name="SubTitleD", fontName=FONT, fontSize=11.2, leading=16,
    textColor=MUTED, spaceAfter=12,
))
styles.add(ParagraphStyle(
    name="H2D", fontName=FONT_BOLD, fontSize=14, leading=18,
    textColor=BLUE, spaceBefore=7, spaceAfter=7,
))
styles.add(ParagraphStyle(
    name="H3D", fontName=FONT_BOLD, fontSize=10.5, leading=14,
    textColor=INK, spaceBefore=4, spaceAfter=3,
))
styles.add(ParagraphStyle(
    name="BodyD", fontName=FONT, fontSize=9.2, leading=13.6,
    textColor=INK, spaceAfter=6,
))
styles.add(ParagraphStyle(
    name="SmallD", fontName=FONT, fontSize=7.6, leading=10.5,
    textColor=MUTED,
))
styles.add(ParagraphStyle(
    name="BulletD", fontName=FONT, fontSize=9, leading=13,
    textColor=INK, leftIndent=12, firstLineIndent=-7, bulletIndent=2,
    spaceAfter=3,
))
styles.add(ParagraphStyle(
    name="CardTitle", fontName=FONT_BOLD, fontSize=10, leading=13,
    textColor=BLUE, spaceAfter=4,
))
styles.add(ParagraphStyle(
    name="CardBody", fontName=FONT, fontSize=8.2, leading=11.3,
    textColor=INK,
))
styles.add(ParagraphStyle(
    name="TableHead", fontName=FONT_BOLD, fontSize=7.5, leading=9.5,
    textColor=WHITE, alignment=TA_LEFT,
))
styles.add(ParagraphStyle(
    name="TableCell", fontName=FONT, fontSize=7.3, leading=9.6,
    textColor=INK,
))
styles.add(ParagraphStyle(
    name="QuoteD", fontName=FONT_BOLD, fontSize=13, leading=18,
    textColor=BLUE, alignment=TA_CENTER, leftIndent=15, rightIndent=15,
))


class Cover(Flowable):
    def __init__(self, width, height):
        super().__init__()
        self.width = width
        self.height = height

    def draw(self):
        c = self.canv
        c.saveState()
        c.setFillColor(NAVY)
        c.rect(-22 * mm, -25 * mm, A4[0] + 44 * mm, A4[1] + 50 * mm, fill=1, stroke=0)
        c.setStrokeColor(HexColor("#0F4569"))
        c.setLineWidth(0.5)
        for x in range(-20, 230, 16):
            c.line(x * mm, -20 * mm, x * mm, 300 * mm)
        for y in range(-20, 320, 16):
            c.line(-20 * mm, y * mm, 230 * mm, y * mm)
        c.setFillColor(HexColor("#071B31"))
        c.circle(182 * mm, 245 * mm, 52 * mm, fill=1, stroke=0)
        c.setStrokeColor(CYAN)
        c.setLineWidth(2)
        c.circle(182 * mm, 245 * mm, 42 * mm, fill=0, stroke=1)

        if LOGO.exists():
            c.drawImage(str(LOGO), 20 * mm, 239 * mm, 34 * mm, 34 * mm, preserveAspectRatio=True, mask="auto")
        c.setFillColor(CYAN)
        c.setFont(FONT_BOLD, 10)
        c.drawString(60 * mm, 261 * mm, "DGCPT")
        c.setFillColor(WHITE)
        c.setFont(FONT_BOLD, 7.6)
        text = c.beginText(60 * mm, 253 * mm)
        text.setLeading(10)
        for line in [
            "DIRECTION GÉNÉRALE DE LA COMPTABILITÉ PUBLIQUE",
            "ET DU TRÉSOR",
        ]:
            text.textLine(line)
        c.drawText(text)

        c.setFillColor(CYAN)
        c.roundRect(20 * mm, 205 * mm, 43 * mm, 7 * mm, 3.5 * mm, fill=1, stroke=0)
        c.setFillColor(NAVY)
        c.setFont(FONT_BOLD, 7.5)
        c.drawCentredString(41.5 * mm, 207.2 * mm, "PRÉSENTATION EXÉCUTIVE")

        c.setFillColor(WHITE)
        c.setFont(FONT_BOLD, 27)
        title = c.beginText(20 * mm, 185 * mm)
        title.setLeading(33)
        title.textLine("PLATEFORME D’AUDIT,")
        title.textLine("DE GOUVERNANCE")
        title.textLine("ET DE PILOTAGE")
        c.drawText(title)

        # Signature de conception, placée immédiatement sous le titre principal.
        c.setFillColor(HexColor("#0B2545"))
        c.setStrokeColor(CYAN)
        c.setLineWidth(1)
        c.roundRect(20 * mm, 132 * mm, 160 * mm, 20 * mm, 4 * mm, fill=1, stroke=1)
        c.setFillColor(CYAN)
        c.setFont(FONT_BOLD, 7.5)
        c.drawString(25 * mm, 146.5 * mm, "CONCEPTION ET RÉALISATION")
        c.setFillColor(WHITE)
        c.setFont(FONT_BOLD, 10)
        c.drawString(
            25 * mm,
            140.5 * mm,
            "Plateforme étudiée, conçue et programmée par CHRYSOSTOME EKINETOU",
        )
        c.setFillColor(HexColor("#BFD2E6"))
        c.setFont(FONT, 9)
        c.drawString(25 * mm, 135.5 * mm, "Inspecteur Vérificateur")

        c.setFillColor(HexColor("#BFD2E6"))
        c.setFont(FONT, 13)
        sub = c.beginText(20 * mm, 78 * mm)
        sub.setLeading(19)
        sub.textLine("Une plateforme multi-niveaux pour organiser les structures,")
        sub.textLine("conduire les missions, maîtriser les risques et éclairer la décision.")
        c.drawText(sub)

        c.setFillColor(GREEN)
        c.rect(20 * mm, 58 * mm, 45 * mm, 2 * mm, fill=1, stroke=0)
        c.setFillColor(HexColor("#9FB3C8"))
        c.setFont(FONT, 8.5)
        c.drawString(20 * mm, 46 * mm, f"DOSSIER DE PRÉSENTATION • VERSION {date.today().strftime('%d/%m/%Y')}")
        c.restoreState()


class ArchitectureDiagram(Flowable):
    def __init__(self, width=170 * mm, height=100 * mm):
        super().__init__()
        self.width, self.height = width, height

    def draw_box(self, c, x, y, w, h, title, subtitle, fill):
        c.setFillColor(fill)
        c.setStrokeColor(HexColor("#B7D7E8"))
        c.roundRect(x, y, w, h, 5, fill=1, stroke=1)
        c.setFillColor(WHITE if fill in [NAVY, BLUE, GREEN] else INK)
        c.setFont(FONT_BOLD, 8.5)
        c.drawCentredString(x + w / 2, y + h - 12, title)
        c.setFont(FONT, 6.7)
        c.drawCentredString(x + w / 2, y + 7, subtitle)

    def draw(self):
        c = self.canv
        c.saveState()
        w = self.width
        self.draw_box(c, 0, 62 * mm, 48 * mm, 22 * mm, "PILOTAGE NATIONAL", "DG • COPRI • RH • Super Admin", NAVY)
        self.draw_box(c, 61 * mm, 62 * mm, 48 * mm, 22 * mm, "GOUVERNANCE", "Référentiels • Méthodes • Contrôles", BLUE)
        self.draw_box(c, 122 * mm, 62 * mm, 48 * mm, 22 * mm, "OBSERVABILITÉ", "Sécurité • Files • Performance • IA", GREEN)
        self.draw_box(c, 10 * mm, 31 * mm, 45 * mm, 20 * mm, "STRUCTURES", "Directions • Pôles • Divisions", PALE)
        self.draw_box(c, 63 * mm, 31 * mm, 45 * mm, 20 * mm, "MISSIONS", "Équipes • Services • Documents", PALE)
        self.draw_box(c, 116 * mm, 31 * mm, 45 * mm, 20 * mm, "RUNTIME", "Workflows • Questionnaires • Terrain", PALE)
        self.draw_box(c, 30 * mm, 2 * mm, 48 * mm, 18 * mm, "RISQUES", "Cartographie • Contrôles • Actions", HexColor("#E8F7F1"))
        self.draw_box(c, 92 * mm, 2 * mm, 48 * mm, 18 * mm, "ANALYSE", "SWOT • RACI • IA assistive", HexColor("#EAF4FF"))
        c.setStrokeColor(CYAN)
        c.setLineWidth(1.5)
        for x1, y1, x2, y2 in [
            (24, 62, 32, 51), (85, 62, 85, 51), (146, 62, 138, 51),
            (32, 31, 54, 20), (85, 31, 54, 20), (138, 31, 116, 20),
        ]:
            c.line(x1 * mm, y1 * mm, x2 * mm, y2 * mm)
        c.restoreState()


class LifecycleDiagram(Flowable):
    def __init__(self, labels, width=170 * mm, height=28 * mm):
        super().__init__()
        self.labels = labels
        self.width, self.height = width, height

    def draw(self):
        c = self.canv
        c.saveState()
        count = len(self.labels)
        gap = 4 * mm
        box_w = (self.width - gap * (count - 1)) / count
        for i, label in enumerate(self.labels):
            x = i * (box_w + gap)
            fill = [BLUE, CYAN, GREEN, YELLOW, NAVY][i % 5]
            c.setFillColor(fill)
            c.setStrokeColor(fill)
            c.roundRect(x, 4 * mm, box_w, 16 * mm, 5, fill=1, stroke=0)
            c.setFillColor(NAVY if fill in [CYAN, YELLOW] else WHITE)
            c.setFont(FONT_BOLD, 6.8 if len(label) > 15 else 7.5)
            c.drawCentredString(x + box_w / 2, 10 * mm, label)
            if i < count - 1:
                c.setStrokeColor(MUTED)
                c.setLineWidth(1)
                c.line(x + box_w, 12 * mm, x + box_w + gap, 12 * mm)
        c.restoreState()


def P(text, style="BodyD"):
    return Paragraph(text, styles[style])


def bullets(items):
    return [Paragraph("• " + item, styles["BulletD"]) for item in items]


def section(kicker, title, subtitle=None):
    out = [P(kicker.upper(), "Kicker"), P(title, "TitleD")]
    if subtitle:
        out.append(P(subtitle, "SubTitleD"))
    out.append(HRFlowable(width="100%", thickness=1, color=LINE, spaceAfter=10))
    return out


def card(title, body, accent=CYAN, width=80 * mm):
    data = [[P(title, "CardTitle")], [P(body, "CardBody")]]
    table = Table(data, colWidths=[width], hAlign="LEFT")
    table.setStyle(TableStyle([
        ("BACKGROUND", (0, 0), (-1, -1), PALE),
        ("BOX", (0, 0), (-1, -1), 0.8, LINE),
        ("LINEBEFORE", (0, 0), (0, -1), 3, accent),
        ("LEFTPADDING", (0, 0), (-1, -1), 9),
        ("RIGHTPADDING", (0, 0), (-1, -1), 9),
        ("TOPPADDING", (0, 0), (-1, 0), 8),
        ("BOTTOMPADDING", (0, -1), (-1, -1), 9),
    ]))
    return table


def two_cards(left, right):
    table = Table([[left, right]], colWidths=[84 * mm, 84 * mm], hAlign="LEFT")
    table.setStyle(TableStyle([("VALIGN", (0, 0), (-1, -1), "TOP"), ("LEFTPADDING", (0, 0), (-1, -1), 0), ("RIGHTPADDING", (0, 0), (-1, -1), 4)]))
    return table


def data_table(headers, rows, widths=None):
    values = [[P(h, "TableHead") for h in headers]]
    values += [[P(str(cell), "TableCell") for cell in row] for row in rows]
    table = Table(values, colWidths=widths, repeatRows=1, hAlign="LEFT")
    table.setStyle(TableStyle([
        ("BACKGROUND", (0, 0), (-1, 0), BLUE),
        ("ROWBACKGROUNDS", (0, 1), (-1, -1), [WHITE, PALE]),
        ("GRID", (0, 0), (-1, -1), 0.45, LINE),
        ("VALIGN", (0, 0), (-1, -1), "TOP"),
        ("LEFTPADDING", (0, 0), (-1, -1), 6),
        ("RIGHTPADDING", (0, 0), (-1, -1), 6),
        ("TOPPADDING", (0, 0), (-1, -1), 6),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 6),
    ]))
    return table


def header_footer(canvas, doc):
    page = canvas.getPageNumber()
    if page == 1:
        return
    canvas.saveState()
    canvas.setStrokeColor(LINE)
    canvas.line(20 * mm, 280 * mm, 190 * mm, 280 * mm)
    if LOGO.exists():
        canvas.drawImage(str(LOGO), 20 * mm, 282 * mm, 9 * mm, 9 * mm, preserveAspectRatio=True, mask="auto")
    canvas.setFont(FONT_BOLD, 6.8)
    canvas.setFillColor(BLUE)
    canvas.drawString(32 * mm, 285 * mm, "DGCPT • PLATEFORME D’AUDIT, DE GOUVERNANCE ET DE PILOTAGE")
    canvas.setFont(FONT, 7)
    canvas.setFillColor(MUTED)
    canvas.drawString(20 * mm, 10 * mm, "Présentation institutionnelle • Document de synthèse")
    canvas.drawRightString(190 * mm, 10 * mm, f"{page:02d}")
    canvas.restoreState()


def build_story():
    S = []
    S += [Cover(170 * mm, 250 * mm), PageBreak()]

    S += section("01 • Vision", "Une plateforme unique pour piloter l’audit à tous les niveaux",
                 "La solution relie l’organisation administrative, les référentiels, les missions, le terrain, les risques et la décision exécutive dans un même environnement sécurisé.")
    S += [two_cards(
        card("UNIFIER", "Mettre fin aux fichiers dispersés et structurer les données d’audit autour d’une mission, d’une unité et d’un référentiel."),
        card("RESPONSABILISER", "Donner à chaque niveau - direction, pôle, division, équipe - les outils correspondant à son mandat.", GREEN),
    ), Spacer(1, 6 * mm), two_cards(
        card("TRACER", "Journaliser les décisions, affectations, validations, documents et changements de statut pour assurer la redevabilité.", YELLOW),
        card("ÉCLAIRER", "Transformer les constats, risques, questionnaires, SWOT et RACI en tableaux de bord exploitables par la gouvernance.", BLUE),
    ), Spacer(1, 8 * mm), P("La plateforme n’est pas seulement un registre. Elle constitue un système de management de l’audit et des risques, depuis la structure administrative jusqu’à la consolidation nationale.", "QuoteD"), PageBreak()]

    S += section("02 • Proposition de valeur", "Ce que la plateforme change concrètement")
    S += [data_table(
        ["Avant", "Avec la plateforme", "Résultat attendu"],
        [
            ["Informations réparties entre Word, Excel et dossiers locaux", "Données structurées par mission, service, questionnaire et risque", "Mémoire institutionnelle consolidée"],
            ["Affectations et responsabilités peu visibles", "Équipes de mission, groupes par questionnaire et matrices RACI", "Responsabilités explicites"],
            ["Questionnaires difficiles à réutiliser", "Bibliothèque, import Word, assistant visuel et version finale adoptée", "Capitalisation méthodologique"],
            ["Cartographies produites tardivement", "Risques capturés pendant les entretiens et projetés dans la cartographie", "Pilotage plus rapide"],
            ["Reporting principalement descriptif", "KPI, maturité, comparaisons, SWOT, recommandations et analyses exécutives", "Décision fondée sur les données"],
        ], [48 * mm, 63 * mm, 57 * mm]
    ), Spacer(1, 8 * mm), P("Bénéfice central", "Kicker"), P("Une même information est saisie une fois, enrichie au fil du workflow, puis restituée selon le niveau de responsabilité.", "QuoteD"), PageBreak()]

    S += section("03 • Architecture fonctionnelle", "Une plateforme multi-niveaux, mais un socle commun")
    S += [ArchitectureDiagram(), Spacer(1, 5 * mm), P("Le modèle sépare clairement trois horizons : le pilotage national, la gouvernance des structures et l’exécution opérationnelle. Les données restent reliées par la mission et par le contexte organisationnel.")]
    S += bullets([
        "Les constructeurs définissent les méthodes : workflows, questionnaires, formulaires, SWOT et RACI.",
        "Le runtime applique ces méthodes à une mission réelle et conserve les preuves d’exécution.",
        "Les tableaux de bord agrègent les résultats sans exposer à chaque utilisateur des données hors de son périmètre.",
    ]) + [PageBreak()]

    S += section("04 • Organisation", "Un organigramme administratif et fonctionnel vivant",
                 "La hiérarchie n’est pas décorative : elle détermine les responsabilités, les droits, les référentiels hérités et la visibilité des missions.")
    S += [data_table(
        ["Niveau", "Exemples de responsables", "Capacités structurantes"],
        [
            ["Direction générale", "Directeur général", "Vue institutionnelle, arbitrage, consolidation"],
            ["Administration / Direction", "Directeur, Directeur adjoint", "Référentiel, missions, supervision des unités"],
            ["Inspection des Services", "Inspecteur des Services", "Pilotage national de l’inspection et validations"],
            ["Pôle / Sous-direction", "Inspecteur adjoint, responsable de structure", "Création des missions, constitution des équipes"],
            ["Division / Service / Cellule", "Chef de service, responsable fonctionnel", "Organisation locale, agents et exécution"],
            ["Agents", "IV, IVA, chargés de vérification, opérationnels", "Contribution selon rôle et mission"],
        ], [42 * mm, 53 * mm, 73 * mm]
    ), Spacer(1, 6 * mm)]
    S += bullets([
        "Constructeur visuel par glisser-déposer, avec contrôle serveur des liens hiérarchiques.",
        "Organigramme global réservé à la Direction générale, aux RH et aux administrateurs.",
        "Organigramme fonctionnel local administrable par le responsable de l’unité.",
        "Choix d’un référentiel d’audit lors de la création des structures porteuses d’un espace d’audit.",
    ]) + [PageBreak()]

    S += section("05 • Expérience par profil", "Le bon niveau d’information pour chaque responsabilité")
    S += [data_table(
        ["Profil", "Vue principale", "Actions clés"],
        [
            ["Direction générale / COPRI", "Vue nationale et exécutive", "Comparer, arbitrer, valider, suivre maturité et risques majeurs"],
            ["Inspecteur des Services", "Portefeuille de missions et validations", "Superviser les pôles, valider les missions, consolider"],
            ["Responsable de direction ou de pôle", "Tableau de bord de son unité", "Créer les missions, choisir le référentiel, affecter les équipes"],
            ["Chef de mission", "Fiche mission et workflow", "Coordonner les groupes, produire les contenus et suivre l’avancement"],
            ["Inspecteur / vérificateur", "Missions visibles et questionnaires", "Auditionner, documenter, identifier les risques, collaborer"],
            ["Administrateur / RH", "IAM, organigramme, sécurité", "Créer les comptes, rattacher les agents, contrôler les habilitations"],
            ["Exploitation", "Santé, files et journaux", "Déployer, sauvegarder, superviser et restaurer"],
        ], [40 * mm, 52 * mm, 76 * mm]
    ), PageBreak()]

    S += section("06 • Missions", "Le dossier numérique complet de l’audit")
    S += [LifecycleDiagram(["PROGRAMMER", "CONSTITUER", "EXÉCUTER", "VALIDER", "CAPITALISER"]), Spacer(1, 6 * mm)]
    S += bullets([
        "Création réservée aux responsables habilités de direction, département, pôle ou structure équivalente.",
        "Visibilité hiérarchique : les agents des divisions voient les missions créées par leur unité parente.",
        "Rôles missionnels distincts des rôles IAM : chef de mission, inspecteur vérificateur, adjoint, expert, observateur ou assistant.",
        "Actions directes : fiche, services, questionnaires, cartographie, rapport PDF et workflow.",
        "Suppression contrôlée des missions encore en brouillon ; conservation de la traçabilité métier.",
    ])
    S += [two_cards(
        card("FICHE MISSION", "Référence, objet, dates, état, équipe, progression, workflow et gouvernance institutionnelle."),
        card("INDICATEURS", "Services audités, entretiens, documents, risques critiques et taux de progression.", GREEN),
    ), PageBreak()]

    S += section("07 • Services et terrain", "Organiser les travaux au niveau réellement audité")
    S += [two_cards(
        card("SERVICES AUDITÉS", "Chaque mission peut couvrir plusieurs services, responsables ou processus. Le niveau de risque et l’état d’audit sont suivis séparément."),
        card("ENTRETIENS", "Les auditions sont rattachées au service, au questionnaire, à la personne interrogée et à l’inspecteur conducteur.", GREEN),
    ), Spacer(1, 5 * mm), two_cards(
        card("DOCUMENTS", "Les pièces attendues et reçues sont conservées par mission, service, entretien, question et groupe d’audit.", YELLOW),
        card("CONSOLIDATION", "Les résultats des services alimentent une synthèse départementale et les tableaux de bord supérieurs.", BLUE),
    ), Spacer(1, 7 * mm)]
    S += bullets([
        "Téléversement et téléchargement des preuves avec métadonnées et journalisation.",
        "Questionnaires remplis au format Word importables dans un groupe pour pré-analyse assistive.",
        "Les résultats restent validés par les inspecteurs avant intégration dans les registres officiels.",
    ]) + [PageBreak()]

    S += section("08 • Questionnaires", "Trois modes complémentaires de création")
    S += [data_table(
        ["Mode", "Usage", "Valeur"],
        [
            ["Bibliothèque institutionnelle", "Créer et versionner des modèles réutilisables", "Capital commun et homogénéité"],
            ["Import Word", "Transformer un questionnaire existant en structure dynamique", "Reprise rapide du patrimoine documentaire"],
            ["Assistant visuel par mission", "Construire thème, thématiques, sous-thèmes et questions pas à pas", "Souplesse pour les missions spécifiques"],
        ], [42 * mm, 67 * mm, 59 * mm]
    ), Spacer(1, 6 * mm), P("Structure standard", "H2D"), LifecycleDiagram(["THÈME", "THÉMATIQUE", "SOUS-THÈME", "QUESTION"]), Spacer(1, 5 * mm)]
    S += bullets([
        "Types de réponses : texte, réponse détaillée, Oui/Non/N.A., date, nombre et capture de risque.",
        "Documents attendus, aide à l’auditeur, caractère obligatoire, observations et détection des risques.",
        "Clonage et versioning pour réutiliser un questionnaire sans altérer sa version publiée.",
    ]) + [PageBreak()]

    S += section("09 • Collaboration", "Un circuit collectif jusqu’à la version finale adoptée")
    S += [LifecycleDiagram(["BROUILLON", "MODIFICATIONS", "RELECTURE", "APPROBATIONS", "ADOPTION"]), Spacer(1, 6 * mm)]
    S += bullets([
        "Tous les inspecteurs autorisés peuvent créer un questionnaire pour une mission visible.",
        "Les libellés des thèmes, thématiques, sous-thèmes et questions sont modifiables collectivement.",
        "Chaque inspecteur peut approuver ou demander une modification avec commentaire.",
        "Toute modification annule les anciens avis et relance le cycle de relecture.",
        "L’adoption exige l’accord du créateur, des inspecteurs affectés, au moins deux avis distincts et aucune correction en attente.",
        "Le responsable hiérarchique prononce l’adoption ; la version finale devient verrouillée et disponible pour les groupes.",
    ])
    S += [P("Accès utilisateur", "H2D"), P("Missions → Questionnaires → Assistant de création visuelle / Relire ou modifier / Avis et adoption."), PageBreak()]

    S += section("10 • Groupes d’audit", "Répartir les inspecteurs par questionnaire et par cible")
    S += [data_table(
        ["Groupe", "Questionnaire", "Cible d’audition", "Composition type"],
        [
            ["Équipe A", "Alignement stratégique", "Responsable stratégie / SDSI", "2 à 3 inspecteurs"],
            ["Équipe B", "Organisation", "Responsable organisation / développement", "2 à 3 inspecteurs"],
            ["Équipe C", "Gouvernance", "Direction / comité de pilotage", "Chef de mission + inspecteurs"],
        ], [30 * mm, 48 * mm, 49 * mm, 41 * mm]
    ), Spacer(1, 7 * mm)]
    S += bullets([
        "Chaque groupe reçoit un questionnaire, des membres, un service audité, une personne à auditionner et un objectif.",
        "Les résultats et documents importés restent attachés au groupe qui les a produits.",
        "La page Questionnaires de la mission présente les créations collaboratives et les affectations opérationnelles.",
    ]) + [PageBreak()]

    S += section("11 • Risques", "De l’observation terrain à la cartographie")
    S += [LifecycleDiagram(["PROCESSUS", "ACTIF", "RISQUE", "CONTRÔLE", "ACTION"]), Spacer(1, 6 * mm)]
    S += [two_cards(
        card("IDENTIFICATION", "Les risques sont créés manuellement ou proposés à partir des réponses de questionnaire. Toute promotion vers le registre exige une validation humaine."),
        card("ÉVALUATION", "Probabilité, impact, criticité, propriétaire, niveau résiduel, contrôles et stratégie de traitement.", GREEN),
    ), Spacer(1, 5 * mm), two_cards(
        card("CARTOGRAPHIE", "Heatmap par mission et vues consolidées pour repérer les concentrations de risques.", YELLOW),
        card("SUIVI", "Actions correctives, responsables, échéances, mitigation, clôture et archivage.", BLUE),
    ), PageBreak()]

    S += section("12 • Workflows", "Industrialiser les méthodes sans figer les métiers")
    S += [P("Le constructeur visuel permet d’assembler des étapes adaptées au type de mission.")]
    S += [data_table(
        ["Famille d’étapes", "Exemples"],
        [
            ["Préparation", "Mission, sélection des services, cadrage"],
            ["Collecte", "Questionnaire, formulaire, revue documentaire"],
            ["Analyse", "Capture des risques, cartographie, SWOT, RACI"],
            ["Décision", "Approbation, rejet, retour, réouverture"],
            ["Clôture", "Plan d’action, reporting, validation institutionnelle"],
        ], [55 * mm, 113 * mm]
    ), Spacer(1, 6 * mm)]
    S += bullets([
        "Brouillon, publication, versioning et archivage des modèles.",
        "Transitions visuelles et ordre d’exécution ; étapes séquentielles ou adaptées au contexte.",
        "Runtime mission avec progression, chronologie, activité et contrôles de transition.",
        "Actions sensibles signées et journalisées : approuver, rejeter, ignorer, relancer, revenir ou rouvrir.",
    ]) + [PageBreak()]

    S += section("13 • SWOT et RACI", "Relier stratégie, organisation et responsabilité")
    S += [two_cards(
        card("SWOT", "Construire et exécuter des matrices Forces, Faiblesses, Opportunités et Menaces ; consolider les recommandations par mission et au niveau exécutif."),
        card("RACI", "Affecter qui est Responsable, Approbateur, Consulté ou Informé ; valider les responsabilités et analyser les surcharges.", GREEN),
    ), Spacer(1, 7 * mm), data_table(
        ["Moment", "SWOT", "RACI"],
        [
            ["Préparation", "Contexte stratégique de l’entité", "Répartition prévisionnelle des responsabilités"],
            ["Terrain", "Forces/faiblesses observées", "Acteurs réellement impliqués"],
            ["Synthèse", "Opportunités, menaces et recommandations", "Écarts, ambiguïtés et responsabilités à clarifier"],
            ["Pilotage", "Consolidation et tendances", "Surcharge, couverture et gouvernance"],
        ], [36 * mm, 66 * mm, 66 * mm]
    ), PageBreak()]

    S += section("14 • Copilote IA", "Assister l’inspecteur sans remplacer son jugement")
    S += [P("Principe de gouvernance", "Kicker"), P("L’IA suggère. L’inspecteur vérifie. Le responsable décide. La plateforme trace.", "QuoteD"), Spacer(1, 7 * mm)]
    S += [data_table(
        ["Capacité", "Apport", "Garde-fou"],
        [
            ["Synthèse de mission", "Résumer constats, entretiens et risques", "Validation humaine obligatoire"],
            ["Questions d’audit", "Proposer des questions adaptées au contexte", "Ajout manuel dans le questionnaire"],
            ["Analyse de risques", "Repérer corrélations, lacunes et tendances", "Aucune création officielle automatique"],
            ["Contrôle interne", "Comparer aux référentiels ISO, COSO, COBIT, ITIL", "Sources et périmètre contextualisés"],
            ["Narration exécutive", "Expliquer les indicateurs et tendances", "Recommandation non contraignante"],
        ], [40 * mm, 67 * mm, 61 * mm]
    ), Spacer(1, 6 * mm)]
    S += bullets([
        "Drivers possibles : mode local simulé, OpenAI, Azure OpenAI ou Ollama sur site.",
        "Modération des requêtes, assainissement des réponses, journalisation des exécutions et isolation par périmètre.",
        "Pour les données sensibles, un moteur sur site peut être privilégié selon la doctrine de sécurité.",
    ]) + [PageBreak()]

    S += section("15 • Pilotage exécutif", "Du détail opérationnel à la vision nationale")
    S += [data_table(
        ["Vue", "Question de management"],
        [
            ["Tableau de bord national", "Quel est l’état global du portefeuille d’audit ?"],
            ["Comparaison des structures", "Quelles directions ou quels pôles nécessitent un soutien ?"],
            ["Intelligence des risques", "Où se concentrent les risques critiques et émergents ?"],
            ["Indice de maturité", "Quel est le niveau de maîtrise par structure ou domaine ?"],
            ["Tableaux SWOT / RACI", "Quelles fragilités et quels problèmes de responsabilité se répètent ?"],
            ["Analyse organisationnelle", "La structure, les rôles et les circuits de décision sont-ils cohérents ?"],
        ], [55 * mm, 113 * mm]
    ), Spacer(1, 7 * mm)]
    S += bullets([
        "Le COPRI dispose d’un espace de pilotage dédié selon habilitation.",
        "Les responsables voient leur périmètre ; les vues nationales restent réservées aux fonctions autorisées.",
        "La consolidation s’appuie sur les mêmes données que les équipes, évitant les ressaisies de reporting.",
    ]) + [PageBreak()]

    S += section("16 • Référentiels", "Adapter l’environnement d’audit à chaque structure")
    S += [two_cards(
        card("RÉFÉRENTIEL PAR DÉFAUT", "Lors de la création d’une structure porteuse, le responsable choisit un référentiel actif. Les unités descendantes héritent de ce choix en lecture."),
        card("ESPACE PROVISIONNÉ", "Workflow, questionnaires, contrôles, taxonomie des risques, modèles SWOT/RACI et règles de périmètre.", GREEN),
    ), Spacer(1, 7 * mm)]
    S += bullets([
        "Catalogue de méthodologies homologuées : référentiels, catégories, contrôles, exigences et correspondances.",
        "Procédures d’audit proposées avec étapes, livrables attendus et questions types.",
        "Possibilité de personnaliser les contenus sans détruire le socle commun fourni par le référentiel.",
        "Le workflow demeure le terme métier conservé pour désigner l’enchaînement des étapes.",
    ]) + [PageBreak()]

    S += section("17 • Sécurité", "Confidentialité, intégrité, traçabilité et disponibilité")
    S += [data_table(
        ["Objectif", "Mécanismes"],
        [
            ["Contrôle d’accès", "Comptes approuvés, rôles, permissions, policies et moindre privilège"],
            ["Isolation", "Visibilité hiérarchique et périmètres départementaux / mission"],
            ["Traçabilité", "Journaux IAM, événements métier, historique des transitions et décisions"],
            ["Intégrité", "Événements immuables, empreintes, signatures des actions sensibles"],
            ["Protection des preuves", "Stockage contrôlé des documents, métadonnées et téléchargements autorisés"],
            ["Disponibilité", "Files de traitement, workers, supervision, sauvegardes et procédures de reprise"],
        ], [48 * mm, 120 * mm]
    ), Spacer(1, 6 * mm)]
    S += bullets([
        "Le journal de sécurité est réservé aux profils autorisés et permet le filtrage par acteur, action, module, date et adresse IP.",
        "Les recommandations IA ne déclenchent aucune action automatique.",
        "La production doit utiliser HTTPS, APP_DEBUG=false, sauvegardes testées et secrets hors du dépôt.",
    ]) + [PageBreak()]

    S += section("18 • Architecture technique", "Un socle moderne, exploitable et évolutif")
    S += [data_table(
        ["Couche", "Technologies / composants"],
        [
            ["Application", "Laravel, PHP 8.3, Blade, politiques d’autorisation"],
            ["Données", "MySQL en production, migrations versionnées, stockage documentaire"],
            ["Interface", "Vite, composants visuels, thème institutionnel DGCPT"],
            ["Asynchrone", "Files de base de données, workers et Horizon"],
            ["Temps réel", "Laravel Reverb sous Supervisor"],
            ["Supervision", "Healthcheck, observabilité, journaux, métriques et diagnostics"],
            ["Déploiement", "GitHub main → VPS Ubuntu → Composer → migrations → caches → Supervisor"],
        ], [45 * mm, 123 * mm]
    ), Spacer(1, 6 * mm), P("Principes d’exploitation", "H2D")]
    S += bullets([
        "Sauvegarder MySQL et les documents avant toute migration structurante.",
        "Mettre l’application en maintenance pendant les mises à jour sensibles.",
        "Vérifier les workers, Reverb, la sonde de santé et le statut Git après chaque livraison.",
    ]) + [PageBreak()]

    S += section("19 • Scénario de démonstration", "Audit du management de la Direction des Systèmes d’Information")
    steps = [
        ["1", "Le responsable du Pôle Informatique crée la mission et désigne le chef de mission."],
        ["2", "Les services audités sont enregistrés : réseau, développement, gouvernance, etc."],
        ["3", "Les inspecteurs créent ou importent les questionnaires Alignement, Organisation et Gouvernance."],
        ["4", "Le collectif relit les thèmes et questions, puis le responsable adopte les versions finales."],
        ["5", "Les inspecteurs sont répartis en groupes ; chaque groupe reçoit un questionnaire et une cible d’audition."],
        ["6", "Les entretiens sont conduits, les réponses et documents attendus sont conservés."],
        ["7", "Les risques proposés sont validés, cartographiés et reliés aux contrôles et actions correctives."],
        ["8", "SWOT, RACI, synthèse IA assistive et rapport alimentent la validation hiérarchique et le pilotage."],
    ]
    S += [data_table(["Étape", "Démonstration"], steps, [18 * mm, 150 * mm]), Spacer(1, 6 * mm), P("Ce scénario montre la continuité complète : organisation → mission → questionnaire → équipe → preuve → risque → décision.", "QuoteD"), PageBreak()]

    S += section("20 • Valeur par niveau", "Une plateforme commune, des bénéfices ciblés")
    S += [data_table(
        ["Niveau", "Bénéfices"],
        [
            ["Institution", "Vision consolidée, normes homogènes, mémoire des audits et redevabilité"],
            ["Direction générale / COPRI", "Arbitrage fondé sur les risques, maturité, tendances et recommandations"],
            ["Inspection des Services", "Portefeuille maîtrisé, équipes visibles, validation et consolidation"],
            ["Directions / Pôles", "Autonomie encadrée, référentiel propre, workflows et ressources adaptés"],
            ["Chefs de mission", "Coordination des travaux, groupes, questionnaires, échéances et livrables"],
            ["Inspecteurs", "Outils terrain structurés, collaboration, documents et traçabilité"],
            ["Administration / RH", "Organigramme fiable, comptes, fonctions, rattachements et sécurité"],
        ], [48 * mm, 120 * mm]
    ), PageBreak()]

    S += section("21 • Conditions de réussite", "Déployer la technologie avec une gouvernance claire")
    S += [two_cards(
        card("GOUVERNANCE", "Désigner les propriétaires des référentiels, les responsables de validation et les administrateurs de données."),
        card("DONNÉES", "Nettoyer les structures, rattacher les agents, définir les rôles et contrôler la qualité des référentiels.", GREEN),
    ), Spacer(1, 5 * mm), two_cards(
        card("ADOPTION", "Former par profils, commencer sur une mission pilote et documenter les procédures réelles.", YELLOW),
        card("EXPLOITATION", "Sécuriser le VPS, HTTPS, sauvegardes, supervision, workers, Reverb et reprise après incident.", BLUE),
    ), Spacer(1, 7 * mm)]
    S += bullets([
        "Pilote recommandé : une mission SI avec trois questionnaires et deux groupes d’audit.",
        "Revue après pilote : droits, ergonomie, délais, qualité des données et pertinence des restitutions.",
        "Généralisation progressive aux autres directions et référentiels après validation institutionnelle.",
    ]) + [PageBreak()]

    S += section("22 • Synthèse", "Une chaîne numérique complète de l’audit à la décision")
    S += [ArchitectureDiagram(height=92 * mm), Spacer(1, 5 * mm), P("La plateforme DGCPT articule quatre forces :", "H2D")]
    S += bullets([
        "une organisation multi-niveaux qui structure les responsabilités ;",
        "des méthodes configurables qui industrialisent les audits sans supprimer l’autonomie métier ;",
        "une exécution traçable qui relie équipes, questionnaires, preuves, risques et actions ;",
        "un pilotage consolidé qui transforme les données opérationnelles en capacité de décision.",
    ])
    S += [Spacer(1, 8 * mm), P("DGCPT", "Kicker"), P("Direction Générale de la Comptabilité Publique et du Trésor", "QuoteD"), PageBreak()]

    S += section("Annexe", "Sources documentaires consultées")
    S += [data_table(
        ["Source", "Objet"],
        [
            ["docs/guides/01-guide-utilisateur.md", "Parcours utilisateurs et cycle mission"],
            ["docs/guides/02-guide-administrateur.md", "IAM, structures, gouvernance et tableaux de bord"],
            ["docs/guides/03-guide-workflows.md", "Conception, publication et exécution des workflows"],
            ["docs/guides/04-guide-ia-copilot.md", "Capacités et gouvernance du copilote IA"],
            ["docs/guides/05-manuel-procedures-securite.md", "Contrôles, incidents et traçabilité"],
            ["docs/guides/06-guide-exploitation.md", "Déploiement, sauvegardes, files et reprise"],
            ["docs/architecture/organisation-institutionnelle.md", "Organigramme et espaces d’audit"],
            ["deploy/PRODUCTION.md", "Architecture et exploitation de production"],
            ["HARDENING_REPORT.md", "Historique d’audit structurel et axes de durcissement"],
            ["Routes, modèles, policies et vues du dépôt", "Vérification des fonctionnalités actuelles, dont les ajouts récents"],
        ], [67 * mm, 101 * mm]
    ), Spacer(1, 8 * mm), P("Note de périmètre", "H2D"), P("Ce document présente les capacités disponibles dans le code et la documentation au 22 juillet 2026. Les fonctions activées en production dépendent des habilitations, des référentiels choisis et de la configuration du VPS."),]
    return S


def main():
    OUT.parent.mkdir(parents=True, exist_ok=True)
    doc = BaseDocTemplate(
        str(OUT), pagesize=A4, leftMargin=20 * mm, rightMargin=20 * mm,
        topMargin=19 * mm, bottomMargin=17 * mm,
        title="Presentation complete de la plateforme DGCPT multi-niveaux",
        author="Direction Generale de la Comptabilite Publique et du Tresor",
        subject="Plateforme d'audit, de gouvernance et de pilotage",
    )
    frame = Frame(doc.leftMargin, doc.bottomMargin, doc.width, doc.height, id="normal")
    doc.addPageTemplates(PageTemplate(id="dgcpt", frames=[frame], onPage=header_footer))
    doc.build(build_story())
    print(OUT)


if __name__ == "__main__":
    main()
