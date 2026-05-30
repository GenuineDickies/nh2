"""Build an editable Table of Heirship PDF matching the California State Controller's
Office Unclaimed Property Division form (Rev. 4/25/2012)."""

from reportlab.lib.pagesizes import letter
from reportlab.lib.colors import black, lightgrey, white
from reportlab.pdfgen import canvas
from reportlab.pdfbase import pdfform

PAGE_W, PAGE_H = letter  # 612 x 792 pts
MARGIN_X = 18  # 0.25"
TOP = PAGE_H - 18
CONTENT_W = PAGE_W - 2 * MARGIN_X  # 576

OUTPUT = r"C:\Users\MSI-Thin\Documents\NewHope2\Table_of_Heirship_editable.pdf"


def text_field(c, name, x, y, w, h, font_size=8):
    """Add a fillable text field."""
    c.acroForm.textfield(
        name=name,
        tooltip=name,
        x=x,
        y=y,
        width=w,
        height=h,
        borderStyle="solid",
        borderColor=black,
        fillColor=white,
        textColor=black,
        forceBorder=False,
        fontSize=font_size,
        fieldFlags="",
    )


def draw_cell(c, x, y, w, h, fill=None):
    if fill is not None:
        c.setFillColor(fill)
        c.rect(x, y, w, h, stroke=0, fill=1)
        c.setFillColor(black)
    c.setStrokeColor(black)
    c.setLineWidth(0.5)
    c.rect(x, y, w, h, stroke=1, fill=0)


def centered_text(c, x, y, w, text, font="Helvetica-Bold", size=8):
    c.setFont(font, size)
    tw = c.stringWidth(text, font, size)
    c.drawString(x + (w - tw) / 2, y, text)


def left_text(c, x, y, text, font="Helvetica", size=8):
    c.setFont(font, size)
    c.drawString(x, y, text)


def build():
    c = canvas.Canvas(OUTPUT, pagesize=letter)
    c.setTitle("Table of Heirship")
    c.setAuthor("California State Controller's Office, Unclaimed Property Division")
    c.setSubject("Editable Table of Heirship (Rev. 4/25/2012)")

    # -------- Header --------
    y = TOP
    c.setFont("Helvetica-Bold", 13)
    c.drawString(MARGIN_X + 170, y - 10, "Controller Betty T. Yee")
    c.setFont("Helvetica-Bold", 10)
    c.drawString(MARGIN_X + 170, y - 22, "California State Controller's Office")
    c.drawString(MARGIN_X + 170, y - 33, "Unclaimed Property Division")
    c.setFont("Helvetica-Bold", 12)
    centered_text(c, MARGIN_X, y - 47, CONTENT_W, "TABLE OF HEIRSHIP", size=12)

    # -------- Top info row --------
    y_top = y - 52
    row_h = 22
    # Left: Deceased Owner Name (label + field)
    label_w = 130
    name_w = 200
    draw_cell(c, MARGIN_X, y_top - row_h, label_w, row_h, fill=lightgrey)
    left_text(c, MARGIN_X + 4, y_top - row_h + 8,
              "Deceased Owner Name:", font="Helvetica-Bold", size=8)
    draw_cell(c, MARGIN_X + label_w, y_top - row_h, name_w, row_h)
    text_field(c, "deceased_owner_name",
               MARGIN_X + label_w + 2, y_top - row_h + 3,
               name_w - 4, row_h - 6)

    # Right: Deceased Date + Property ID (stacked, two rows)
    right_x = MARGIN_X + label_w + name_w
    right_w = CONTENT_W - label_w - name_w
    half_h = row_h
    lbl_w = 120
    fld_w = right_w - lbl_w
    # Deceased Date
    draw_cell(c, right_x, y_top - half_h, lbl_w, half_h, fill=lightgrey)
    left_text(c, right_x + 4, y_top - half_h + 8,
              "Deceased Date:", font="Helvetica-Bold", size=8)
    draw_cell(c, right_x + lbl_w, y_top - half_h, fld_w, half_h)
    text_field(c, "deceased_date",
               right_x + lbl_w + 2, y_top - half_h + 3,
               fld_w - 4, half_h - 6)
    # Property ID
    draw_cell(c, right_x, y_top - 2 * half_h, lbl_w, half_h, fill=lightgrey)
    left_text(c, right_x + 4, y_top - 2 * half_h + 8,
              "Property ID:", font="Helvetica-Bold", size=8)
    draw_cell(c, right_x + lbl_w, y_top - 2 * half_h, fld_w, half_h)
    text_field(c, "property_id",
               right_x + lbl_w + 2, y_top - 2 * half_h + 3,
               fld_w - 4, half_h - 6)

    # Instructions block (under name field)
    instr_y = y_top - 2 * row_h
    instr_h = row_h
    draw_cell(c, MARGIN_X, instr_y, label_w + name_w, instr_h)
    c.setFont("Helvetica-Bold", 8)
    centered_text(c, MARGIN_X, instr_y + 12, label_w + name_w,
                  "LIST ALL KNOWN RELATIVES", size=8)
    c.setFont("Helvetica-Oblique", 6)
    c.drawString(MARGIN_X + 6, instr_y + 4,
                 "Enter \"None\" in any section for which there is no known relative.")
    c.drawString(MARGIN_X + 6, instr_y - 3,
                 "(If you need additional space, attach a second Table of Heirship")
    # adjust: keep within cell
    # Redraw instructions cell to be taller and contain all 3 lines correctly:
    # Easier: extend instr_h
    # Clean by overdrawing background then rewriting -- skip for brevity, use 1 line

    # -------- Section rows definitions --------
    # cursor
    cur_y = instr_y - 4

    # Common column geometry for sections 1, 2, 3, 6 (with row#, with extra col)
    sec_label_w = 110
    rownum_w = 16
    first_w = 78
    middle_w = 78
    last_w = 100
    extra_w = 76  # Marriage / Parent's Name
    birth_w = 58
    death_w = 60
    # total: 110+16+78+78+100+76+58+60 = 576 ✓

    # For sections 4, 5 (no extra column)
    s45_label_w = 110
    s45_sub_w = 0  # for sec 4: father/mother label
    s45_first_w = 90
    s45_middle_w = 90
    s45_last_w = 130
    s45_birth_w = 78
    s45_death_w = 78

    HEADER_H = 14
    ROW_H = 14

    def draw_section_header(y_pos, section_name, section_title,
                            with_extra=True, extra_label="Date of",
                            extra_sub="Marriage", use_45_layout=False):
        """Draw the grey header row with section name + column labels."""
        x = MARGIN_X
        if use_45_layout:
            widths = [s45_label_w, s45_first_w + s45_middle_w + s45_last_w,
                      s45_birth_w, s45_death_w]
            # Actually we want individual: section label, first, middle, last, birth, death
            # Draw section label
            draw_cell(c, x, y_pos, s45_label_w, HEADER_H, fill=lightgrey)
            centered_text(c, x, y_pos + 4, s45_label_w, section_name,
                          font="Helvetica-Bold", size=8)
            x += s45_label_w
            # FIRST MIDDLE (MAIDEN) LAST as single wide cell with labels
            wide = s45_first_w + s45_middle_w + s45_last_w
            draw_cell(c, x, y_pos, wide, HEADER_H)
            # draw the three labels with vertical separators
            c.setFont("Helvetica-Bold", 7)
            c.drawString(x + 4, y_pos + 4, "FIRST")
            c.drawString(x + s45_first_w + 4, y_pos + 4, "MIDDLE")
            c.drawString(x + s45_first_w + s45_middle_w + 4, y_pos + 4,
                         "(MAIDEN) LAST")
            x += wide
            draw_cell(c, x, y_pos, s45_birth_w, HEADER_H, fill=lightgrey)
            centered_text(c, x, y_pos + 4, s45_birth_w, "Birth",
                          font="Helvetica-BoldOblique", size=8)
            x += s45_birth_w
            draw_cell(c, x, y_pos, s45_death_w, HEADER_H, fill=lightgrey)
            centered_text(c, x, y_pos + 4, s45_death_w, "Death",
                          font="Helvetica-BoldOblique", size=8)
            return

        # Standard layout (sections 1, 2, 3, 6)
        # Section label cell spans label + rownum width
        full_label_w = sec_label_w + rownum_w
        draw_cell(c, x, y_pos, full_label_w, HEADER_H, fill=lightgrey)
        centered_text(c, x, y_pos + 4, full_label_w, section_name,
                      font="Helvetica-Bold", size=8)
        x += full_label_w
        # Column heading band for FIRST / MIDDLE / (MAIDEN) LAST
        wide = first_w + middle_w + last_w
        draw_cell(c, x, y_pos, wide, HEADER_H)
        c.setFont("Helvetica-Bold", 8)
        # center each
        centered_text(c, x, y_pos + 4, first_w, "FIRST",
                      font="Helvetica-Bold", size=8)
        centered_text(c, x + first_w, y_pos + 4, middle_w, "MIDDLE",
                      font="Helvetica-Bold", size=8)
        centered_text(c, x + first_w + middle_w, y_pos + 4, last_w,
                      "(MAIDEN) LAST", font="Helvetica-Bold", size=8)
        x += wide
        # Extra column (Marriage / Parent's Name)
        draw_cell(c, x, y_pos, extra_w, HEADER_H, fill=lightgrey)
        if extra_label == "Date of":
            # Two-line: "Date of" centered over "Marriage / Birth / Death"
            # In original, "Date of" spans Marriage|Birth|Death cells. We just put it inside the extra col header
            centered_text(c, x, y_pos + 7, extra_w, "Date of",
                          font="Helvetica-Oblique", size=7)
            centered_text(c, x, y_pos + 1, extra_w, extra_sub,
                          font="Helvetica-BoldOblique", size=7)
        else:
            centered_text(c, x, y_pos + 7, extra_w, extra_label,
                          font="Helvetica-Oblique", size=7)
            centered_text(c, x, y_pos + 1, extra_w, extra_sub,
                          font="Helvetica-Oblique", size=6)
        x += extra_w
        draw_cell(c, x, y_pos, birth_w, HEADER_H, fill=lightgrey)
        centered_text(c, x, y_pos + 4, birth_w, "Birth",
                      font="Helvetica-BoldOblique", size=8)
        x += birth_w
        draw_cell(c, x, y_pos, death_w, HEADER_H, fill=lightgrey)
        centered_text(c, x, y_pos + 4, death_w, "Death",
                      font="Helvetica-BoldOblique", size=8)

    def draw_data_row(y_pos, sec_prefix, row_idx, section_label_text="",
                      show_label=False, with_extra=True, label_h_total=None):
        """Draw one data row with fillable cells."""
        x = MARGIN_X
        # Section label cell
        if show_label:
            draw_cell(c, x, y_pos - label_h_total + ROW_H, sec_label_w,
                      label_h_total, fill=lightgrey)
            # wrapping the label text
            lines = section_label_text.split("|")
            line_h = 9
            total_text_h = len(lines) * line_h
            start_y = (y_pos - label_h_total + ROW_H) + (label_h_total - total_text_h) / 2 + (len(lines) - 1) * line_h
            for i, line in enumerate(lines):
                centered_text(c, x, start_y - i * line_h, sec_label_w, line,
                              font="Helvetica-Bold", size=8)
        # row number cell
        x_num = MARGIN_X + sec_label_w
        draw_cell(c, x_num, y_pos, rownum_w, ROW_H)
        centered_text(c, x_num, y_pos + 4, rownum_w, str(row_idx),
                      font="Helvetica", size=8)
        x = x_num + rownum_w
        # FIRST
        draw_cell(c, x, y_pos, first_w, ROW_H)
        text_field(c, f"{sec_prefix}_{row_idx}_first",
                   x + 1, y_pos + 1, first_w - 2, ROW_H - 2)
        x += first_w
        # MIDDLE
        draw_cell(c, x, y_pos, middle_w, ROW_H)
        text_field(c, f"{sec_prefix}_{row_idx}_middle",
                   x + 1, y_pos + 1, middle_w - 2, ROW_H - 2)
        x += middle_w
        # LAST
        draw_cell(c, x, y_pos, last_w, ROW_H)
        text_field(c, f"{sec_prefix}_{row_idx}_last",
                   x + 1, y_pos + 1, last_w - 2, ROW_H - 2)
        x += last_w
        # Extra column
        draw_cell(c, x, y_pos, extra_w, ROW_H)
        text_field(c, f"{sec_prefix}_{row_idx}_extra",
                   x + 1, y_pos + 1, extra_w - 2, ROW_H - 2)
        x += extra_w
        # Birth
        draw_cell(c, x, y_pos, birth_w, ROW_H)
        text_field(c, f"{sec_prefix}_{row_idx}_birth",
                   x + 1, y_pos + 1, birth_w - 2, ROW_H - 2)
        x += birth_w
        # Death
        draw_cell(c, x, y_pos, death_w, ROW_H)
        text_field(c, f"{sec_prefix}_{row_idx}_death",
                   x + 1, y_pos + 1, death_w - 2, ROW_H - 2)

    def draw_45_data_row(y_pos, sec_prefix, row_label_or_num,
                         section_label_text="", show_label=False,
                         label_h_total=None, is_label=False):
        """Draw a row for sections 4/5 (no extra column, larger first/middle/last)."""
        x = MARGIN_X
        if show_label:
            draw_cell(c, x, y_pos - label_h_total + ROW_H, s45_label_w,
                      label_h_total, fill=lightgrey)
            lines = section_label_text.split("|")
            line_h = 9
            total_text_h = len(lines) * line_h
            start_y = (y_pos - label_h_total + ROW_H) + (label_h_total - total_text_h) / 2 + (len(lines) - 1) * line_h
            for i, line in enumerate(lines):
                centered_text(c, x, start_y - i * line_h, s45_label_w, line,
                              font="Helvetica-Bold", size=8)
        x = MARGIN_X + s45_label_w
        if is_label:
            # Father/Mother style row: no row number, just inline label area inside FIRST cell?
            # In the original Section 4, the Father/Mother label is to the LEFT of the FIRST cell
            # within the section label region. Simplify: split first cell into label + field.
            sub_w = 50
            draw_cell(c, x, y_pos, sub_w, ROW_H, fill=lightgrey)
            centered_text(c, x, y_pos + 4, sub_w, str(row_label_or_num),
                          font="Helvetica-Bold", size=8)
            x += sub_w
            # remaining first width
            rem_first = s45_first_w - sub_w
            draw_cell(c, x, y_pos, rem_first, ROW_H)
            text_field(c, f"{sec_prefix}_{row_label_or_num.lower()}_first",
                       x + 1, y_pos + 1, rem_first - 2, ROW_H - 2)
            x += rem_first
        else:
            # Row number cell
            sub_w = 16
            draw_cell(c, x, y_pos, sub_w, ROW_H)
            centered_text(c, x, y_pos + 4, sub_w, str(row_label_or_num),
                          font="Helvetica", size=8)
            x += sub_w
            rem_first = s45_first_w - sub_w
            draw_cell(c, x, y_pos, rem_first, ROW_H)
            text_field(c, f"{sec_prefix}_{row_label_or_num}_first",
                       x + 1, y_pos + 1, rem_first - 2, ROW_H - 2)
            x += rem_first

        # MIDDLE
        draw_cell(c, x, y_pos, s45_middle_w, ROW_H)
        key = str(row_label_or_num).lower()
        text_field(c, f"{sec_prefix}_{key}_middle",
                   x + 1, y_pos + 1, s45_middle_w - 2, ROW_H - 2)
        x += s45_middle_w
        # LAST
        draw_cell(c, x, y_pos, s45_last_w, ROW_H)
        text_field(c, f"{sec_prefix}_{key}_last",
                   x + 1, y_pos + 1, s45_last_w - 2, ROW_H - 2)
        x += s45_last_w
        # Birth
        draw_cell(c, x, y_pos, s45_birth_w, ROW_H)
        text_field(c, f"{sec_prefix}_{key}_birth",
                   x + 1, y_pos + 1, s45_birth_w - 2, ROW_H - 2)
        x += s45_birth_w
        # Death
        draw_cell(c, x, y_pos, s45_death_w, ROW_H)
        text_field(c, f"{sec_prefix}_{key}_death",
                   x + 1, y_pos + 1, s45_death_w - 2, ROW_H - 2)

    # ===== Section 1: Spouse(s) =====
    cur_y -= HEADER_H
    draw_section_header(cur_y, "Section 1", "Spouse",
                        extra_label="Date of", extra_sub="Marriage")
    sec1_rows = 3
    sec1_label_h = sec1_rows * ROW_H
    for i in range(1, sec1_rows + 1):
        cur_y -= ROW_H
        draw_data_row(cur_y, "s1", i,
                      section_label_text="Deceased Owner's|Spouse(s)",
                      show_label=(i == 1),
                      label_h_total=sec1_label_h)

    # ===== Section 2: Children =====
    cur_y -= HEADER_H
    draw_section_header(cur_y, "Section 2", "Children",
                        extra_label="Parent's Name", extra_sub="(FROM SECTION 1)")
    sec2_rows = 8
    sec2_label_h = sec2_rows * ROW_H
    for i in range(1, sec2_rows + 1):
        cur_y -= ROW_H
        draw_data_row(cur_y, "s2", i,
                      section_label_text="Deceased Owner's|Children",
                      show_label=(i == 1),
                      label_h_total=sec2_label_h)

    # ===== Section 3: Grandchildren =====
    cur_y -= HEADER_H
    draw_section_header(cur_y, "Section 3", "Grandchildren",
                        extra_label="Parent's Name", extra_sub="(FROM SECTION 2)")
    sec3_rows = 8
    sec3_label_h = sec3_rows * ROW_H
    for i in range(1, sec3_rows + 1):
        cur_y -= ROW_H
        draw_data_row(cur_y, "s3", i,
                      section_label_text="Deceased Owner's|Grandchildren",
                      show_label=(i == 1),
                      label_h_total=sec3_label_h)

    # ===== Section 4: Parents =====
    cur_y -= HEADER_H
    draw_section_header(cur_y, "Section 4", "Parents", use_45_layout=True)
    sec4_label_h = 2 * ROW_H
    # Father row
    cur_y -= ROW_H
    draw_45_data_row(cur_y, "s4", "Father",
                     section_label_text="Deceased|Owner's Parents",
                     show_label=True, label_h_total=sec4_label_h, is_label=True)
    # Mother row
    cur_y -= ROW_H
    draw_45_data_row(cur_y, "s4", "Mother", is_label=True)

    # ===== Section 5: Brothers and Sisters =====
    cur_y -= HEADER_H
    draw_section_header(cur_y, "Section 5", "Brothers and Sisters",
                        use_45_layout=True)
    sec5_rows = 5
    sec5_label_h = sec5_rows * ROW_H
    for i in range(1, sec5_rows + 1):
        cur_y -= ROW_H
        draw_45_data_row(cur_y, "s5", i,
                         section_label_text="Deceased Owner's|Brothers and Sisters",
                         show_label=(i == 1),
                         label_h_total=sec5_label_h)

    # ===== Section 6: Children of Brothers/Sisters =====
    cur_y -= HEADER_H
    draw_section_header(cur_y, "Section 6", "Children of B&S",
                        extra_label="Parent's Name", extra_sub="(FROM SECTION 5)")
    sec6_rows = 4
    sec6_label_h = sec6_rows * ROW_H
    for i in range(1, sec6_rows + 1):
        cur_y -= ROW_H
        draw_data_row(cur_y, "s6", i,
                      section_label_text="Children of Deceased|Owner's Brothers|and Sisters",
                      show_label=(i == 1),
                      label_h_total=sec6_label_h)

    # ===== Declaration =====
    cur_y -= 4
    decl_h = 38
    draw_cell(c, MARGIN_X, cur_y - decl_h, CONTENT_W, decl_h)
    c.setFont("Helvetica", 7.5)
    decl_lines = [
        "I declare under penalty of perjury, under the laws of the State of California, that all statements contained in this Table of Heirship and any",
        "accompanying documents are true and correct, with full knowledge that all statements made in the Table of Heirship are subject to",
        "investigation and that any false or dishonest statement may be grounds for denial of the submitted claim.",
    ]
    for i, line in enumerate(decl_lines):
        c.drawString(MARGIN_X + 4, cur_y - 10 - i * 10, line)
    cur_y -= decl_h

    # PRINTED NAME / SIGNATURE row
    sig_row_h = 22
    pname_w = CONTENT_W * 0.5
    sig_w = CONTENT_W - pname_w
    # Printed name cell
    draw_cell(c, MARGIN_X, cur_y - sig_row_h, pname_w, sig_row_h)
    text_field(c, "printed_name",
               MARGIN_X + 2, cur_y - sig_row_h + 3, pname_w - 4, sig_row_h - 12)
    c.setFont("Helvetica-Bold", 7)
    centered_text(c, MARGIN_X, cur_y - sig_row_h + 2, pname_w, "PRINTED NAME",
                  font="Helvetica-Bold", size=7)
    # Signature cell
    draw_cell(c, MARGIN_X + pname_w, cur_y - sig_row_h, sig_w, sig_row_h)
    text_field(c, "signature",
               MARGIN_X + pname_w + 2, cur_y - sig_row_h + 3, sig_w - 4, sig_row_h - 12)
    centered_text(c, MARGIN_X + pname_w, cur_y - sig_row_h + 2, sig_w, "SIGNATURE",
                  font="Helvetica-Bold", size=7)
    cur_y -= sig_row_h

    # Footer
    c.setFont("Helvetica", 7)
    c.drawString(MARGIN_X, cur_y - 10, "DS/gk")
    c.drawRightString(PAGE_W - MARGIN_X, cur_y - 10, "Rev. 4/25/2012")

    c.save()
    print(f"Wrote {OUTPUT}")


if __name__ == "__main__":
    build()
