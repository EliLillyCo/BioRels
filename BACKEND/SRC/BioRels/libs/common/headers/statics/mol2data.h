#ifndef MOL2DATA_H
#define MOL2DATA_H


#include "headers/statics/atomdata.h"
#define NB_MOL2 57

static const struct
{
    std::string mol2;
    unsigned char atomic_num;

}MOL2_TYPE[NB_MOL2]=
{
    {"H"    ,1 },
    {"C.3"  ,6 },
    {"C.2"  ,6 },
    {"C.1"  ,6 },
    {"C.ar" ,6 },
    {"C.cat",6 },
    {"N.3"  ,7 },
    {"N.2"  ,7 },
    {"N.1"  ,7 },
    {"N.am" ,7 },
    {"N.pl3",7 },
    {"N.ar" ,7 },
    {"N.4"  ,7 },
    {"O.3"  ,8 },
    {"O.2"  ,8 },
    {"O.co2",8 },
    {"O.spc",8 },
    {"O.t3p",8 },
    {"S.3"  ,16},
    {"S.2"  ,16},
    {"S.O"  ,16},
    {"S.o2" ,16},
    {"P.3"  ,15},
    {"F"    ,9},
    {"Cl",17 },
    {"Br",35 },
    {"I",53 },
    {"Zn",30 },
    {"Se",34 },
    {"Io",42 },
    {"Sn",50 },
    {"H.spc",1 },
    {"H.t3p",1 },
    {"LP",DUMMY_ATM },
    {"Du",DUMMY_ATM },
    {"Du.C",DUMMY_ATM },
    {"Hal",DUMMY_ATM },
    {"Het",DUMMY_ATM },
    {"Hev",DUMMY_ATM },
    {"Li",3},
    {"Na",11 },
    {"Mg",12 },
    {"Al",13 },
    {"Ca",20 },
    {"Cr",24 },
    {"Cr.th",24 },
    {"Cr.oh",24 },
    {"Mn",25 },
    {"Fe", 26},
    {"Co.oh",27 },
    {"Cu",29 },
    {"B",5},
    {"K",19},
    {"Mo",42},
    {"DuAr",DUMMY_ATM},
    {"DuCy",DUMMY_ATM},
    {"Met",DUMMY_ATM}


};


#endif // MOL2DATA_H

