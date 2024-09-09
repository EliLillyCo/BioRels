#ifndef ATOMDATA_H
#define ATOMDATA_H

#include <string>
#define NNATM 103
#define NNATM_OUT NNATM+1
#define DUMMY_ATM 0

typedef  unsigned char aelem;

static const struct
{
    unsigned char number;
    bool isBioRelevant;
    char name[3];
    std::string fullname;
    double weigth;
    double vdw;
    double covRadii;/// Covalent radii revisited - 10.1039/b801115j
    short valence;

} Periodic[NNATM]=
{
    {DUMMY_ATM, 0,   "Du",  "Dummy",        0,      0,      0   ,0},
    {1, 1,           "H",	"Hydrogen",	    1.008,	1.2,    0.31,1},
    {2,	0,           "He",	"Helium",   	4.002,	1.4,    0.28,0},
    {3,	0,           "Li",	"Lithium",  	6.94,	0,      1.28,1},
    {4,	0,           "Be",	"Beryllium",	9.01,	0,      0.96,2},
    {5,	0 ,          "B",	"Boron",	   10.81,	1.65,   0.84,3},
    {6,	1,           "C",	"Carbon",	   12.011,	1.7,    0.76,4},// From Bondi 1964. covRadii is for sp3. Csp2=0.73. Csp=0.69
    {7,	1,           "N",	"Nitrogen",    14.007,	1.55,   0.71,3},
    {8,	1,           "O",	"Oxygen",	   15.999,	1.52,   0.66,2},//From Bondi 1964
    {9,	1,           "F",	"Fluorine",    18.998,	1.47,   0.57,1},//From Bondi 1964
    {10,0,          "Ne",	"Neon",        20.1797,	0,      0.58,0},
    {11,0,          "Na",	"Sodium",      22.989,	0,      1.66,1},
    {12,1,          "Mg",	"Magnesium",   24.305,	1.73,   1.41,2},//wiki
    {13,0,          "Al",	"Aluminium",   26.98,	0,      1.21,3},
    {14,0,          "Si",	"Silicon",     28.08,	2.1,    1.11,4},
    {15,1,          "P",	"Phosphorus",  30.97,	1.8,    1.07,5},
    {16,1,          "S",	"Sulfur",      32.06,	1.85,   1.05,2}, //From Bondi 1964 (1.8) Modified to 1.85 because of methionine. Valence should be 6
    {17,1,          "Cl",	"Chlorine",    35.45,	1.8,    1.02,5}, // vdw Median taken from doi:10.1023/A:1011625728803
    {18,0,          "Ar",	"Argon",       39.95,	0,      1.06,0},
    {19,1,          "K",	"Potassium",   39.09,	2.75,   2.03,1},
    {20,1,          "Ca",	"Calcium",     40.08,	0,      1.76,2},
    {21,0,          "Sc",	"Scandium",    44.96,	0,      1.70,3},
    {22,0,			"Ti",	"Titanium",    47.87,	0,      1.60,4},
    {23,0,			"V",	"Vanadium",    50.94,	0,      1.53,5},
    {24,0,			"Cr",	"Chromium",    51.99,	0,      1.39,6},
    {25,1,			"Mn",	"Manganese",   54.94,	1.26,   1.39,4},
    {26,1,			"Fe",	"Iron",        55.85,	2.7,    1.32,3},
    {27,1,			"Co",	"Cobalt",      58.93,	0,      1.26,4},
    {28,0,			"Ni",	"Nickel",      58.69,	1.63,   1.24,2},//Wikipedia for vdW
    {29,0,			"Cu",	"Copper",      63.55,	2.5,    1.32,2},// Fake value needed for 1aoz C1O/C2O
    {30,1,			"Zn",	"Zinc",        65.38,	1.39,   1.22,2}, //Wikipedia
    {31,0,			"Ga",	"Gallium",     69.72,	0,      1.22,3},
    {32,0,			"Ge",	"Germanium",   72.63,	0,      1.20,4},
    {33,0,			"As",	"Arsenic",     74.92,	1.85,   1.19,5},
    {34,0,			"Se",	"Selenium",    78.96,	1.9,    1.20,6},
    {35,1,			"Br",	"Bromine",     79.9,	1.90,   1.20,7},// vdw Median taken from doi:10.1023/A:1011625728803
    {36,0,			"Kr",	"Krypton",     83.79,	0,      1.16,2},
    {37,0,			"Rb",	"Rubidium",    85.46,	0,      2.20,1},
    {38,0,			"Sr",	"Strontium",   87.62,	0,      1.95,2},
    {39,0,			"Y",	"Yttrium",     88.9,	0,      1.90,3},
    {40,0,			"Zr",	"Zirconium",   91.22,	0,      1.75,4},
    {41,0,			"Nb",	"Niobium",     92.9,	0,      1.64,5},
    {42,0,			"Mo",	"Molybdenum",  95.96,	0,      1.54,6},
    {43,0,			"Tc",	"Technetium",  98,      0,      1.47,7},
    {44,0,			"Ru",	"Ruthenium",  101,      0,      1.46,6},
    {45,0,			"Rh",	"Rhodium",	  102.9,	0,      1.42,6},
    {46,0,			"Pd",	"Palladium",  106.42,	0,      1.39,4},
    {47,0,			"Ag",	"Silver",	  107.86,	0,      1.45,2},
    {48,0,			"Cd",	"Cadmium",	  112.41,	0,      1.44,2},
    {49,0,			"In",	"Indium",	  114.82,	0,      1.42,3},
    {50,0,			"Sn",	"Tin",        118.71,	0,      1.39,4},
    {51,0,			"Sb",	"Antimony",	  121.76,	0,      1.38,5},
    {52,0,			"Te",	"Tellurium",  127.6,	0,      1.39,6},
    {53,1,			"I",	"Iodine",	  126.9,	2.10,   1.40,7},  // vdw Median taken from doi:10.1023/A:1011625728803
    {54,0,			"Xe",	"Xenon",	  131.3,	0,      2.44,6},
    {55,0,			"Cs",	"Caesium",	  132.9,	0,      2.15,1},
    {56,0,			"Ba",	"Barium",	  137.3,	0,      2.07,2},
    {57,0,			"La",	"Lanthanum",  138.9,	0,      2.04,3},
    {58,0,			"Ce",	"Cerium",	  140.1,	0,      2.03,4},
    {59,0,			"Pr",	"Praseodymium",140.1,	0,      2.01,4},
    {60,0,			"Nd",	"Neodymium",  144.24,	0,      1.99,3},
    {61,0,          "Pm",   "Promethium",0,0,      0,3},
    {62,0,          "Sm",   "Samarium", 0,0,      0,3},
    {63,0,          "Eu",   "Europium", 0,0,      0,3},
    {64,0,     "Gd",   "Gadolinium", 0,0,      0,3},
    {65,0,     "Tb",   "", 0,0,      0,3},
    {66,0,     "Dy",   "", 0,0,      0,3},
    {67,0,     "Ho",   "", 0,0,      0,3},
    {68,0,     "Er",   "", 0,0,      0,3},
    {69,0,     "Tm",   "", 0,0,      0,3},
    {70,0,     "Yb",   "", 0,0,      0,3},
    {71,0,     "Lu",   "", 0,0,      0,3},
    {72,0,     "Hf",   "", 0,0,      0,4},
    {73,0,     "Ta",   "", 0,0,      0,5},
    {74,0,     "W",   "", 0,0,      0,6},
    {75,0,     "Re",   "", 0,0,      0,7},
    {76,0,     "Os",   "", 0,0,      0,6},
    {77,0,     "Ir",   "", 0,0,      0,6},
    {78,0,     "Pt",   "", 0,0,      0,6},
    {79,0,     "Au",   "", 0,0,      0,5},
    {80,0,     "Hg",   "", 0,2.5,   1.8,2},
    {81,0,     "Tl",   "", 0,0,      0,3},
    {82,0,     "Pb",   "", 0,0,      0,4},
    {83,0,     "Bi",   "", 0,0,      0,5},
    {84,0,     "Po",   "", 0,0,      0,6},
    {85,0,     "At",   "", 0,0,      0,7},
    {86,0,     "Rn",   "", 0,0,      0,6},
    {87,0,     "Fr",   "", 0,0,      0,3},
    {88,0,     "Ra",   "", 0,0,      0,2},
    {89,0,     "Ac",   "", 0,0,      0,3},
    {90,0,     "Th",   "", 0,0,      0,4},
    {91,0,     "Pa",   "", 0,0,      0,5},
    {92,0,     "U",   "", 0,0,      0,6},
    {93,0,     "Np",  "", 0,0,      0,6},
    {94,0,"Pu","",0,0,      0,6},
    {95,0,"Am","",0,0,      0,4},
    {96,0,"Cm","",0,0,      0,4},
    {97,0,"Bk","",0,0,      0,4},
    {98,0,"Cf","",0,0,      0,4},
    {99,0,"Es","",0,0,      0,4},
    {100,0,"Fm","",0,0,      0,3},
    {101,0,"Md","",0,0,      0,3},
    {102,0,"No","",0,0,0,3}

};

namespace protspace
{
unsigned char nameToNum(const std::string& name);
}
#endif // ATOMDATA_H

