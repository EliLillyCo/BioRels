#include "headers/proc/atomperception.h"
#include "headers/statics/intertypes.h"
#include "headers/molecule/mmatom.h"
#include "headers/molecule/mmbond.h"
#include "headers/molecule/macromole.h"
#include "headers/molecule/mmatom_utils.h"
#include "headers/statics/logger.h"
#undef NDEBUG /// Active assertion in release
///TODO Handle thiazol situation with double bond on sulfur
/// ISSUE : 2AS
const std::vector<protspace::AtomRule> protspace::AtomPerception::mCarbonRules={
    {6,{{6,BOND::SINGLE},{6,BOND::DOUBLE}},"C.ar",true,true,0},///Regular aromatic//93618
    {6,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{6,BOND::SINGLE}},"C.ar",true,true,0},///001:C01//34052
    {6,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{8,BOND::SINGLE}},"C.ar",true,true,0},///001:C03/C04/C05//9893
    {6,{{6,BOND::DOUBLE},{7,BOND::SINGLE},{6,BOND::SINGLE}},"C.ar",true,true,0},///25V:C9 Kekule//16306
    {6,{{6,BOND::DOUBLE},{7,BOND::SINGLE}},"C.ar",true,true,0},///2839430:C next to NO Kekule//5474
    {6,{{6,BOND::SINGLE},{7,BOND::SINGLE},{7,BOND::DOUBLE}},"C.2",false,false,0},/// 0CA:C7//4286
    {6,{{6,BOND::DOUBLE},{7,BOND::SINGLE},{7,BOND::SINGLE}},"C.ar",true,true,0},///L17:C21 Kekule//3322
    {6,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{16,BOND::SINGLE}},"C.ar",true,true,0},///00I:C2//3291
    {6,{{7,BOND::SINGLE},{7,BOND::DOUBLE}},"C.ar",true,true,0}, /// 00A:C2//3272
    {6,{{6,BOND::SINGLE},{7,BOND::DOUBLE}},"C.ar",true,true,0},///062:C6 - Kekule - no H//3239
    {6,{{6,BOND::SINGLE},{6,BOND::SINGLE},{7,BOND::DOUBLE}},"C.2",false,false,0},/// 0A3:C13//2912
    {6,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{17,BOND::SINGLE}},"C.ar",true,true,0},///Aromatic C -Cl//2780
    {6,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{9,BOND::SINGLE}},"C.ar",true,true,0},///008:C1//2176
    {6,{{6,BOND::SINGLE},{6,BOND::SINGLE},{8,BOND::DOUBLE}},"C.2",false,false,0},///01A:C//2136
    {6,{{7,BOND::SINGLE},{7,BOND::SINGLE},{7,BOND::DOUBLE}},"C.2",false,false,0},/// GAI:C//2059
    {6,{{6,BOND::SINGLE},{8,BOND::DOUBLE},{8,BOND::SINGLE}},"C.2",false,false,0},///001:C17//1748
    {6,{{8,BOND::SINGLE},{8,BOND::DOUBLE},{7,BOND::SINGLE}},"C.2",false,false,0},/// 017:C21//692
    {6,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{35,BOND::SINGLE}},"C.ar",true,true,0},///Aromatic C - Br//668
    {6,{{6,BOND::DOUBLE},{16,BOND::SINGLE}},"C.2",false,false,0},///SC=C//746
    {6,{{16,BOND::SINGLE},{7,BOND::DOUBLE},{7,BOND::SINGLE}},"C.2",true,false,0},///2836176 SC(N)N//707
    {6,{{6,BOND::SINGLE},{7,BOND::TRIPLE}},"C.1",false,false,0},/// 03U:C23//642
    {6,{{6,BOND::SINGLE},{6,BOND::TRIPLE}},"C.1",false,false,0},/// 06U:CAL/CAK//554
    {6,{{6,BOND::DOUBLE}},"C.2",false,false,0},/// 0US:C16//476
    {6,{{6,BOND::SINGLE},{7,BOND::DOUBLE},{8,BOND::SINGLE}},"C.ar",true,true,0},///INO:C2 Kekule//474
    {6,{{6,BOND::SINGLE},{8,BOND::DOUBLE}},"C.2",false,false,0},///0AO:C2//439
    {6,{{6,BOND::SINGLE},{7,BOND::DOUBLE},{16,BOND::SINGLE}},"C.ar",true,true,0},///00N:C1X//429
    {6,{{6,BOND::DOUBLE},{7,BOND::SINGLE},{8,BOND::SINGLE}},"C.ar",true,true,0},///INO:C2 Kekule//269
    {6,{{6,BOND::DOUBLE},{8,BOND::SINGLE}},"C.2",false,false,0},///3355063 OC=C//234
    {6,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{53,BOND::SINGLE}},"C.ar",true,true,0},///Aromatic C - I//209
    {6,{{6,BOND::DOUBLE},{7,BOND::SINGLE},{16,BOND::SINGLE}},"C.ar",true,true,0},///039:C6//198
    {6,{{6,BOND::SINGLE},{8,BOND::DOUBLE},{16,BOND::SINGLE}},"C.2",false,false,0},///07O:CD//130
    {6,{{7,BOND::SINGLE},{8,BOND::DOUBLE}},"C.2",false,false,0},///175783 O=C-N//116
    {6,{{6,BOND::DOUBLE},{16,BOND::SINGLE},{16,BOND::SINGLE}},"C.ar",true,true,0},///03T:C2//95
    {6,{{6,BOND::TRIPLE}},"C.1",false,false,0},///91
    {6,{{7,BOND::DOUBLE},{16,BOND::SINGLE}},"C.ar",true,true,0},///013:C4//83
    {6,{{6,BOND::DOUBLE},{7,BOND::SINGLE},{17,BOND::SINGLE}},"C.ar",true,true,0},///087:C21//58
    {6,{{6,BOND::DOUBLE},{16,BOND::SINGLE},{17,BOND::SINGLE}},"C.2",true,true,0},/// 0H7:C20/C14//58
    {6,{{6,BOND::SINGLE},{7,BOND::DOUBLE},{17,BOND::SINGLE}},"C.ar",true,true,0},///087:C21//51
    {6,{{7,BOND::SINGLE},{8,BOND::DOUBLE},{16,BOND::SINGLE}},"C.2",false,false,0},///0A8:C1//45
    {6,{{6,BOND::DOUBLE},{7,BOND::SINGLE},{35,BOND::SINGLE}},"C.ar",true,true,0},///BrC(C)=N//17
    {6,{{6,BOND::DOUBLE},{8,BOND::SINGLE},{16,BOND::SINGLE}},"C.2",true,true,0},/// 0GX:CAN//17
    {6,{{8,BOND::SINGLE},{8,BOND::DOUBLE},{8,BOND::SINGLE}},"C.2",false,false,0},///000:C//16
    {6,{{6,BOND::SINGLE},{6,BOND::SINGLE},{16,BOND::DOUBLE}},"C.2",true,false,0},/// 0F5:C14 => Wrong Sulfur//16
    {6,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{1,BOND::SINGLE}},"C.ar",true,true,0},///Regular aromatic//15
    {6,{{6,BOND::SINGLE},{7,BOND::DOUBLE},{9,BOND::SINGLE}},"C.ar",true,true,0},///1RP:CAP//15
    {6,{{6,BOND::DOUBLE},{7,BOND::SINGLE},{9,BOND::SINGLE}},"C.ar",true,true,0},///1RP:CAP//14
    {6,{{7,BOND::SINGLE},{16,BOND::DOUBLE}},"C.2",false,false,0},/// 0FI:C4//10
    {6,{{6,BOND::DOUBLE},{6,BOND::DOUBLE}},"C.2",false,false,0},/// 0CN:C30//8
    {6,{{6,BOND::SINGLE},{7,BOND::DOUBLE},{35,BOND::SINGLE}},"C.ar",true,true,0},///BrC(C)=N//5
    {6,{{6,BOND::SINGLE},{16,BOND::DOUBLE}},"C.2",true,false,0},/// 0F2:CAZ => Wrong Sulfur//4
    {6,{{6,BOND::DOUBLE},{1,BOND::SINGLE},{1,BOND::SINGLE}},"C.2",false,false,0},///0US:C16//3
    {6,{{6,BOND::DOUBLE},{7,BOND::SINGLE},{53,BOND::SINGLE}},"C.ar",true,true,0},///IC(C)=N//3
    {6,{{6,BOND::DOUBLE},{17,BOND::SINGLE},{17,BOND::SINGLE}},"C.2",false,false,0},///0D1:CAM//3
    {6,{{6,BOND::SINGLE},{16,BOND::DOUBLE},{1,BOND::SINGLE}},"C.2",true,false,0},/// 0F2:CAZ => Wrong Sulfur//2



    {6,{{6,BOND::AROMATIC_BD},{16,BOND::AROMATIC_BD},{17,BOND::SINGLE}},"C.2",true,true,0},/// 0H7:C20/C14//0
    {6,{{7,BOND::DOUBLE}},"C.2",true,true,0},/// 0HQ:C1//11
    {6,{{7,BOND::DOUBLE},{1,BOND::SINGLE},{1,BOND::SINGLE}},"C.2",true,true,0},/// 0HQ:C1
    {6,{{6,BOND::DOUBLE},{34,BOND::SINGLE}},"C.ar",true,true,0},/// 0JZ:C21/C07/C14
    {6,{{6,BOND::AROMATIC_BD},{34,BOND::AROMATIC_BD}},"C.ar",true,true,0},/// 0JZ:C21/C07/C14
    {6,{{6,BOND::DOUBLE},{34,BOND::SINGLE},{1,BOND::SINGLE}},"C.ar",true,true,0},/// 0JZ:C21/C07/C14
    {6,{{6,BOND::AROMATIC_BD},{34,BOND::AROMATIC_BD},{1,BOND::SINGLE}},"C.ar",true,true,0},/// 0JZ:C21/C07/C14
    {6,{{6,BOND::SINGLE},{7,BOND::DOUBLE},{34,BOND::SINGLE}},"C.ar",true,true,0},/// 0JZ:C10/C12/C05
    {6,{{6,BOND::SINGLE},{7,BOND::AROMATIC_BD},{34,BOND::AROMATIC_BD}},"C.ar",true,true,0},/// 0JZ:C10/C12/C05
    {6,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{15,BOND::SINGLE}},"C.ar",true,true,0},///0KB:CAQ
    {6,{{6,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD},{15,BOND::SINGLE}},"C.ar",true,true,0},///0KB:CAQ
    {6,{{6,BOND::SINGLE},{7,BOND::DOUBLE},{15,BOND::SINGLE}},"C.ar",true,true,0},///cc(P)n
    {6,{{6,BOND::DOUBLE},{7,BOND::SINGLE},{15,BOND::SINGLE}},"C.ar",true,true,0},///cc(P)n
    {6,{{6,BOND::AROMATIC_BD},{7,BOND::AROMATIC_BD},{15,BOND::SINGLE}},"C.ar",true,true,0},///cc(P)n
    {6,{{7,BOND::SINGLE},{7,BOND::SINGLE},{16,BOND::DOUBLE}},"C.2",true,false,0},///0KY:C9
    {6,{{7,BOND::TRIPLE},{8,BOND::SINGLE}},"C.1",false,false,0},///0NM:C
    {6,{{7,BOND::SINGLE},{7,BOND::DOUBLE},{8,BOND::SINGLE}},"C.ar",true,true,0},///0OV:C11
    {6,{{6,BOND::SINGLE},{7,BOND::SINGLE},{16,BOND::DOUBLE}},"C.2",false,false,0},///0QV:CAM
    {6,{{7,BOND::SINGLE},{16,BOND::SINGLE},{16,BOND::DOUBLE}},"C.2",true,false,0},///0RR:CAZ
    {6,{{7,BOND::DOUBLE},{16,BOND::SINGLE},{16,BOND::SINGLE}},"C.2",true,false,0},///0TE:C12
    {6,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{51,BOND::SINGLE}},"C.ar",true,true,0},///118:C1B/C1C/C1A/C1D
    {6,{{6,BOND::DOUBLE},{8,BOND::SINGLE},{35,BOND::SINGLE}},"C.ar",true,true,0},///14J:C04
    {6,{{6,BOND::AROMATIC_BD},{8,BOND::AROMATIC_BD},{35,BOND::SINGLE}},"C.ar",true,true,0},///14J:C04
    {6,{{6,BOND::DOUBLE},{6,BOND::SINGLE},{34,BOND::SINGLE}},"C.ar",true,true,0},///182:CAP/CAQ
    {6,{{6,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD},{34,BOND::SINGLE}},"C.ar",true,true,0},///182:CAP/CAQ
    {6,{{7,BOND::SINGLE},{7,BOND::DOUBLE},{17,BOND::SINGLE}},"C.ar",true,true,0},///1B3:C25
    {6,{{7,BOND::AROMATIC_BD},{7,BOND::AROMATIC_BD},{17,BOND::SINGLE}},"C.ar",true,true,0},///1B3:C25
    {6,{{7,BOND::SINGLE},{7,BOND::DOUBLE},{35,BOND::SINGLE}},"C.ar",true,true,0},///BrC(N)=N
    {6,{{7,BOND::AROMATIC_BD},{7,BOND::AROMATIC_BD},{35,BOND::SINGLE}},"C.ar",true,true,0},///BrC(N)=N
    {6,{{7,BOND::SINGLE},{7,BOND::DOUBLE},{9,BOND::SINGLE}},"C.ar",true,true,0},///FC(N)=N
    {6,{{7,BOND::AROMATIC_BD},{7,BOND::AROMATIC_BD},{9,BOND::SINGLE}},"C.ar",true,true,0},///FC(N)=N
    {6,{{7,BOND::SINGLE},{7,BOND::DOUBLE},{53,BOND::SINGLE}},"C.ar",true,true,0},///IC(N)=N
    {6,{{7,BOND::AROMATIC_BD},{7,BOND::AROMATIC_BD},{53,BOND::SINGLE}},"C.ar",true,true,0},///IC(N)=N
    {6,{{6,BOND::DOUBLE},{16,BOND::SINGLE},{35,BOND::SINGLE}},"C.ar",true,true,0},///1B8:C16
    {6,{{6,BOND::AROMATIC_BD},{16,BOND::AROMATIC_BD},{35,BOND::SINGLE}},"C.ar",true,true,0},///1B8:C16
    {6,{{6,BOND::DOUBLE},{16,BOND::SINGLE},{9,BOND::SINGLE}},"C.ar",true,true,0},///FC(S)=C
    {6,{{6,BOND::AROMATIC_BD},{16,BOND::AROMATIC_BD},{9,BOND::SINGLE}},"C.ar",true,true,0},///FC(S)=C
    {6,{{6,BOND::DOUBLE},{16,BOND::SINGLE},{17,BOND::SINGLE}},"C.ar",true,true,0},///ClC(S)=C
    {6,{{6,BOND::AROMATIC_BD},{16,BOND::AROMATIC_BD},{17,BOND::SINGLE}},"C.ar",true,true,0},///ClC(S)=C
    {6,{{6,BOND::DOUBLE},{16,BOND::SINGLE},{53,BOND::SINGLE}},"C.ar",true,true,0},///IC(S)=C
    {6,{{6,BOND::AROMATIC_BD},{16,BOND::AROMATIC_BD},{53,BOND::SINGLE}},"C.ar",true,true,0},///IC(S)=C
    {6,{{6,BOND::DOUBLE},{6,BOND::SINGLE},{5,BOND::SINGLE}},"C.ar",true,true,0},///1C0:C20
    {6,{{6,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD},{5,BOND::SINGLE}},"C.ar",true,true,0},///1C0:C20
    {6,{{6,BOND::SINGLE},{16,BOND::SINGLE},{16,BOND::DOUBLE}},"C.2",false,false,0},///1CZ:C1
    {6,{{7,BOND::DOUBLE},{16,BOND::DOUBLE}},"C.2",false,false,0},///1X2:C02
    {6,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{14,BOND::SINGLE}},"C.ar",true,true,0},///21P:C8/C7
    {6,{{6,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD},{14,BOND::SINGLE}},"C.ar",true,true,0},///21P:C8/C7
    {6,{{14,BOND::SINGLE},{14,BOND::SINGLE}},"C.3",false,false,0},///21P:C3
    {6,{{14,BOND::SINGLE},{14,BOND::SINGLE},{1,BOND::SINGLE},{1,BOND::SINGLE}},"C.3",false,false,0},///21P:C3
    {6,{{14,BOND::SINGLE},{1,BOND::SINGLE},{1,BOND::SINGLE},{1,BOND::SINGLE}},"C.3",false,false,0},///21P:C1/C2/C5/C6
    {6,{{14,BOND::SINGLE}},"C.3",false,false,0},///21P:C1/C2/C5/C6
    {6,{{6,BOND::DOUBLE},{16,BOND::DOUBLE}},"C.2",false,false,0},///22W:C
    {6,{{6,BOND::DOUBLE},{7,BOND::SINGLE},{34,BOND::SINGLE}},"C.ar",true,true,0},///23S:CE2
    {6,{{6,BOND::AROMATIC_BD},{7,BOND::AROMATIC_BD},{34,BOND::SINGLE}},"C.ar",true,true,0},///23S:CE2
    {6,{{6,BOND::AROMATIC_BD},{7,BOND::AROMATIC_BD},{34,BOND::AROMATIC_BD}},"C.ar",true,true,0},///23S:CE2
    {6,{{5,BOND::SINGLE},{6,BOND::DOUBLE}},"C.2",false,false,0},///2BH:C09
    {6,{{5,BOND::SINGLE},{6,BOND::DOUBLE},{1,BOND::SINGLE}},"C.2",false,false,0},///2BH:C09
    {6,{{5,BOND::SINGLE},{6,BOND::DOUBLE},{16,BOND::SINGLE}},"C.ar",true,true,0},///2GK:C9
    {6,{{5,BOND::SINGLE},{6,BOND::AROMATIC_BD},{16,BOND::AROMATIC_BD}},"C.ar",true,true,0},///2GK:C9
    {6,{{6,BOND::DOUBLE},{15,BOND::SINGLE}},"C.2",false,false,0},///2O2:CAS
    {6,{{6,BOND::DOUBLE},{15,BOND::SINGLE},{1,BOND::SINGLE}},"C.2",false,false,0},///2O2:CAS
    {6,{{7,BOND::SINGLE},{7,BOND::TRIPLE}},"C.1",false,false,0},///2QG:C17
    {6,{{8,BOND::TRIPLE},{77,BOND::SINGLE}},"C.1",false,false,0},///2T8:C
    {6,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{80,BOND::SINGLE}},"C.ar",true,true,0},/// 31Q:C07
    {6,{{6,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD},{80,BOND::SINGLE}},"C.ar",true,true,0},/// 31Q:C07
    {6,{{7,BOND::DOUBLE},{8,BOND::SINGLE},{16,BOND::SINGLE}},"C.ar",true,true,0},/// 34O:C12
    {6,{{7,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD},{16,BOND::SINGLE}},"C.ar",true,true,0},/// 34O:C12
    {6,{{7,BOND::SINGLE},{8,BOND::SINGLE},{16,BOND::DOUBLE}},"C.2",true,false,0},/// 3HT:C21
    {6,{{6,BOND::TRIPLE},{15,BOND::SINGLE}},"C.1",false,false,0},/// 3JE:C20
    {6,{{7,BOND::DOUBLE},{8,BOND::SINGLE}},"C.ar",true,true,0},/// 3JG:
    {6,{{7,BOND::DOUBLE},{8,BOND::SINGLE},{1,BOND::SINGLE}},"C.ar",true,true,0},/// 3JG:CAL
    {6,{{7,BOND::AROMATIC_BD},{8,BOND::AROMATIC_BD}},"C.ar",true,true,0},/// 3JG:CAK
    {6,{{7,BOND::AROMATIC_BD},{8,BOND::AROMATIC_BD},{1,BOND::SINGLE}},"C.ar",true,true,0},/// 3JG:CAL
    {6,{{7,BOND::TRIPLE},{16,BOND::SINGLE}},"C.1",false,false,0},/// 3SC:C25
    {6,{{7,BOND::DOUBLE},{16,BOND::SINGLE},{17,BOND::SINGLE}},"C.ar",true,true,0}, ///3V1:C12
    {6,{{8,BOND::DOUBLE},{26,BOND::SINGLE},{26,BOND::SINGLE}},"C.2",true,false,0},///402:C5
    {6,{{8,BOND::TRIPLE},{26,BOND::SINGLE}},"C.1",true,false,0},///402:C3/C7
    {6,{{7,BOND::TRIPLE},{26,BOND::SINGLE}},"C.1",true,false,0},///402:C4/C6
    {6,{{6,BOND::TRIPLE},{8,BOND::SINGLE}},"C.1",false,false,0},/// 45W:C49
    {6,{{6,BOND::DOUBLE},{6,BOND::SINGLE},{26,BOND::SINGLE}},"C.ar",true,true,0},///4HE:C1
    {6,{{6,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD},{26,BOND::SINGLE}},"C.ar",true,true,0},///4HE:C1
    {6,{{8,BOND::DOUBLE},{8,BOND::SINGLE}},"C.2",false,false,0},/// 4OZ:C25
    {6,{{8,BOND::DOUBLE},{8,BOND::SINGLE},{1,BOND::SINGLE}},"C.2",false,false,0},/// 4OZ:C25
    {6,{{7,BOND::DOUBLE},{7,BOND::SINGLE},{79,BOND::SINGLE}},"C.ar",true,true,0},/// 51O:C10/C1
    {6,{{7,BOND::AROMATIC_BD},{7,BOND::AROMATIC_BD},{79,BOND::SINGLE}},"C.ar",true,true,0},/// 51O:C10/C1
    {6,{{6,BOND::AROMATIC_BD},{8,BOND::AROMATIC_BD},{17,BOND::SINGLE}},"C.ar",true,true,0},/// 6C0:CAU
    {6,{{6,BOND::DOUBLE},{8,BOND::SINGLE},{17,BOND::SINGLE}},"C.ar",true,true,0},/// 6C0:CAU
    {6,{{7,BOND::TRIPLE}},"C.1",false,false,0},/// 6CU:CAE
    {6,{{7,BOND::SINGLE},{7,BOND::SINGLE},{79,BOND::DOUBLE}},"C.2",true,false,0},/// 6O0:C1
    {6,{{7,BOND::SINGLE},{7,BOND::DOUBLE},{44,BOND::SINGLE}},"C.2",true,false,0},/// 9RU:C20
    {6,{{8,BOND::TRIPLE},{44,BOND::SINGLE}},"C.1",false,false,0},/// AG1:C12
    {6,{{6,BOND::SINGLE},{8,BOND::SINGLE},{16,BOND::DOUBLE}},"C.2",false,false,0},/// ALT:C
    {6,{{6,BOND::SINGLE},{8,BOND::DOUBLE},{15,BOND::SINGLE}},"C.2",false,false,0},/// ALU:C2
    {6,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{33,BOND::SINGLE}},"C.ar",true,true,0},/// ASR:C1
    {6,{{6,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD},{33,BOND::SINGLE}},"C.ar",true,true,0},/// ASR:C1
    {6,{{7,BOND::TRIPLE},{79,BOND::SINGLE}},"C.1",false,false,0},/// AUC:C1/C2
    {6,{{6,BOND::DOUBLE},{17,BOND::SINGLE}},"C.2",false,false,0},/// B89:CAE
    {6,{{6,BOND::DOUBLE},{17,BOND::SINGLE},{1,BOND::SINGLE}},"C.2",false,false,0},/// B89:CAE
    {6,{{6,BOND::DOUBLE},{9,BOND::SINGLE}},"C.2",false,false,0},/// FC=C
    {6,{{6,BOND::DOUBLE},{9,BOND::SINGLE},{1,BOND::SINGLE}},"C.2",false,false,0},/// FC=C
    {6,{{6,BOND::DOUBLE},{53,BOND::SINGLE}},"C.2",false,false,0},/// IC=C
    {6,{{6,BOND::DOUBLE},{53,BOND::SINGLE},{1,BOND::SINGLE}},"C.2",false,false,0},/// IC=C
    {6,{{6,BOND::DOUBLE},{35,BOND::SINGLE}},"C.2",false,false,0},/// BrC=C
    {6,{{6,BOND::DOUBLE},{35,BOND::SINGLE},{1,BOND::SINGLE}},"C.2",false,false,0},/// BrC=C
    {6,{{8,BOND::DOUBLE},{8,BOND::DOUBLE}},"C.2",false,false,0},/// CO2
    {6,{{6,BOND::TRIPLE},{7,BOND::SINGLE}},"C.1",false,false,0},/// DR8:NAC
    {6,{{6,BOND::DOUBLE},{9,BOND::SINGLE},{15,BOND::SINGLE}},"C.2",false,false,0},///EN0:C11
    {6,{{6,BOND::DOUBLE},{8,BOND::SINGLE},{8,BOND::SINGLE}},"C.ar",true,true,0},///F63:C8
    {6,{{8,BOND::DOUBLE},{26,BOND::DOUBLE}},"C.2",false,false,0},///FCO:C3
    {6,{{6,BOND::SINGLE},{8,BOND::DOUBLE},{26,BOND::SINGLE}},"C.2",false,false,0},///FE9:C
    {6,{{8,BOND::DOUBLE},{1,BOND::SINGLE},{1,BOND::SINGLE}},"C.2",false,false,0},///FLH:C
    {6,{{8,BOND::DOUBLE}},"C.2",false,false,0},///FLH:C
    {6,{{8,BOND::DOUBLE},{16,BOND::SINGLE}},"C.2",false,false,0},///FYN:CM1
    {6,{{8,BOND::DOUBLE},{16,BOND::SINGLE},{1,BOND::SINGLE}},"C.2",false,false,0},///FYN:CM1
    {6,{{7,BOND::SINGLE},{8,BOND::DOUBLE}, {15,BOND::SINGLE}},"C.2",false,false,0},///IS2:C12
    {6,{{7,BOND::SINGLE},{7,BOND::DOUBLE}, {34,BOND::SINGLE}},"C.2",false,false,0},///ISU:C3
    {6,{{16,BOND::SINGLE},{16,BOND::SINGLE}, {16,BOND::DOUBLE}},"C.2",false,false,0},///KCS:C
    {6,{{8,BOND::DOUBLE},{26,BOND::SINGLE}},"C.2",false,false,0},///NFB:C3/C1
    {6,{{8,BOND::DOUBLE},{26,BOND::SINGLE},{1,BOND::SINGLE}},"C.2",false,false,0},///NFB:C3/C1
    {6,{{8,BOND::SINGLE},{8,BOND::DOUBLE}, {16,BOND::SINGLE}},"C.2",false,false,0},///OHS:CZ
    {6,{{7,BOND::SINGLE},{16,BOND::TRIPLE}},"C.1",false,false,0},///OSV:C
    {6,{{8,BOND::SINGLE},{8,BOND::DOUBLE},{17,BOND::SINGLE}},"C.2",false,false,0},///PHQ:C1
    {6,{{6,BOND::DOUBLE},{8,BOND::DOUBLE}},"C.2",false,false,0},///PN2::C
    {6,{{8,BOND::SINGLE},{8,BOND::DOUBLE},{15,BOND::SINGLE}},"C.2",false,false,0},///PPF:C1
    {6,{{6,BOND::SINGLE},{8,BOND::DOUBLE},{17,BOND::SINGLE}},"C.2",false,false,0},///PR6:CAH
    {6,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{44,BOND::SINGLE}},"C.ar",false,false,0}, ///RPS:C32
    {6,{{6,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD},{44,BOND::SINGLE}},"C.ar",false,false,0}, ///RPS:C32
    {6,{{7,BOND::TRIPLE},{34,BOND::SINGLE}},"C.1",false,false,0},///SEK:C
    {6,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{44,BOND::SINGLE}},"C.ar",false,false,0}, ///RPS:C32
    {6,{{6,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD},{44,BOND::SINGLE}},"C.ar",false,false,0}, ///RPS:C32
    {6,{{6,BOND::SINGLE},{7,BOND::SINGLE},{34,BOND::DOUBLE}},"C.2",true,false,0}, ///SNI:C6
    {6,{{6,BOND::DOUBLE},{15,BOND::SINGLE},{15,BOND::SINGLE}},"C.2",false,false,0}, ///SRL:C8
    {6,{{6,BOND::DOUBLE},{15,BOND::SINGLE},{15,BOND::SINGLE},{1,BOND::SINGLE}},"C.2",false,false,0}, ///SRL:C8
    {6,{{6,BOND::DOUBLE},{6,BOND::SINGLE},{46,BOND::SINGLE}},"C.2",true,false,0}, ///SVP:C
    {6,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{50,BOND::SINGLE}},"C.ar",true,true,0}, ///T9T:C1/C8/C14
    {6,{{6,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD},{50,BOND::SINGLE}},"C.ar",true,true,0}, ///T9T:C1/C8/C14
    {6,{{7,BOND::TRIPLE},{28,BOND::SINGLE}},"C.1",false,false,0}, ///TCN:C1/C2/C3/C4
    {6,{{6,BOND::SINGLE},{6,BOND::SINGLE},{52,BOND::DOUBLE}},"C.2",true,false,0}, ///TTI:C5
    {6,{{6,BOND::SINGLE},{7,BOND::AROMATIC_BD}},"C.ar",true,true,0}, ///062:C6
    {6,{{6,BOND::DOUBLE},{7,BOND::AROMATIC_BD}},"C.ar",true,true,0}, ///062:C2
    {6,{{6,BOND::SINGLE},{6,BOND::SINGLE},{7,BOND::AROMATIC_BD}},"C.ar",true,true,0}, ///170:C2 - This is because of nitro group
    {6,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{7,BOND::AROMATIC_BD}},"C.ar",true,true,0}, ///25V:C9 - This is because of nitro group
    {6,{{6,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::SINGLE},{7,BOND::AROMATIC_BD}},"C.3",false,false,0}, ///4PO:C4 - This is because of nitro group
    {6,{{6,BOND::SINGLE},{7,BOND::AROMATIC_BD},{16,BOND::SINGLE}},"C.ar",true,true,0}, ///BEW:C2 - This is because of nitro group
    {6,{{6,BOND::SINGLE},{7,BOND::AROMATIC_BD},{8,BOND::SINGLE}},"C.ar",true,true,0}, ///INO:C2 - This is because of nitro group
    {6,{{6,BOND::DOUBLE},{7,BOND::SINGLE},{7,BOND::AROMATIC_BD}},"C.ar",true,true,0}, ///L17:C2 - This is because of nitro group
    {6,{{7,BOND::DOUBLE},{7,BOND::SINGLE},{7,BOND::AROMATIC_BD}},"C.ar",true,true,0}, ///MXD:CAA - This is because of nitro group
    {6,{{6,BOND::SINGLE},{7,BOND::SINGLE},{7,BOND::AROMATIC_BD}},"C.ar",true,true,0}, ///MXD:CAE - This is because of nitro group
    {6,{{7,BOND::SINGLE},{7,BOND::SINGLE},{34,BOND::DOUBLE}},"C.2",false,false,0}, ///SEY:C
    {6,{{6,BOND::DOUBLE},{6,BOND::SINGLE}},"C.ar",true,true,0},///Regular aromatic//0
    {6,{{6,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD}},"C.ar",true,true,0},///Regular aromatic//0
    {6,{{6,BOND::DOUBLE},{6,BOND::SINGLE},{1,BOND::SINGLE}},"C.ar",true,true,0},///Regular aromatic//0
    {6,{{6,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD},{1,BOND::SINGLE}},"C.ar",true,true,0},///Regular aromatic//0
    {6,{{6,BOND::TRIPLE},{1,BOND::SINGLE}},"C.1",false,false,0},//0
    {6,{{6,BOND::DOUBLE},{6,BOND::SINGLE},{1,BOND::SINGLE}},"C.2",false,false,0},/// 0US:C15//0
    {6,{{6,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::SINGLE},{7,BOND::SINGLE}},"C.3",false,false,0},/// 4P0:C4//0
    {6,{{6,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::DOUBLE},{45,BOND::SINGLE}},"C.2",true,false,1},/// 0OD:C13/C14/C15/C16//0
    {6,{{6,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::DOUBLE},{44,BOND::SINGLE}},"C.2",true,false,1},/// HB1:C5/C6/C7/C8/C9//0
    {6,{{1,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::DOUBLE},{44,BOND::SINGLE}},"C.2",true,false,1},/// HB1:C5/C6/C7/C8/C9//0
    {6,{{6,BOND::SINGLE},{7,BOND::DOUBLE},{1,BOND::SINGLE}},"C.ar",true,true,0},///062:C6 - Kekule - w H//0
    {6,{{6,BOND::AROMATIC_BD},{7,BOND::AROMATIC_BD},{1,BOND::SINGLE}},"C.ar",true,true,0},///062:C6 - Aromatic - w H//0
    {6,{{6,BOND::AROMATIC_BD},{7,BOND::AROMATIC_BD}},"C.ar",true,true,0},///062:C6 - Aromatic - no H//0
    {6,{{6,BOND::AROMATIC_BD},{7,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD}},"C.ar",true,true,0},///25V:C9 Aromatic//0
    {6,{{6,BOND::SINGLE},{7,BOND::DOUBLE},{6,BOND::SINGLE}},"C.ar",true,true,0},///25V:C9 Kekule//0
    {6,{{6,BOND::AROMATIC_BD},{7,BOND::AROMATIC_BD},{8,BOND::SINGLE}},"C.ar",true,true,0},///INO:C2 Aromatic//0
    {6,{{6,BOND::SINGLE},{7,BOND::DOUBLE},{7,BOND::SINGLE}},"C.ar",true,true,0},///L17:C21 Kekule//0
    {6,{{6,BOND::AROMATIC_BD},{7,BOND::AROMATIC_BD},{7,BOND::SINGLE}},"C.ar",true,true,0},///L17:C21 Aromatic//0
    {6,{{6,BOND::SINGLE},{7,BOND::DOUBLE}},"C.ar",true,true,0},///2839430:C next to NO Kekule//0
    {6,{{6,BOND::AROMATIC_BD},{7,BOND::AROMATIC_BD}},"C.ar",true,true,0},///2839430:C next to NO Aromatic//0
    {6,{{1,BOND::SINGLE},{7,BOND::SINGLE},{8,BOND::DOUBLE}},"C.2",false,false,0},///175783 O=C-N with H//0
    {6,{{6,BOND::DOUBLE},{8,BOND::SINGLE},{1,BOND::SINGLE}},"C.2",false,false,0},///3355063 OC=C//0
    {6,{{6,BOND::DOUBLE},{7,BOND::SINGLE}},"C.2",false,false,0},///387309 NC=C//0
    {6,{{6,BOND::DOUBLE},{7,BOND::SINGLE},{1,BOND::SINGLE}},"C.2",false,false,0},///387309 NC=C//0
    {6,{{6,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD},{6,BOND::SINGLE}},"C.ar",true,true,0},///001:C01//0
    {6,{{6,BOND::DOUBLE},{16,BOND::SINGLE},{1,BOND::SINGLE}},"C.2",false,false,0},///SC=C//0
    {6,{{6,BOND::SINGLE},{7,BOND::DOUBLE},{6,BOND::SINGLE}},"C.2",true,false,0},///3ZU:C5//0
    {6,{{6,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD},{8,BOND::SINGLE}},"C.ar",true,true,0},///001:C03/C04/C05//0
    {6,{{6,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD},{9,BOND::SINGLE}},"C.ar",true,true,0},///008:C1//0
    {6,{{6,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD},{17,BOND::SINGLE}},"C.ar",true,true,0},///Aromatic C- Cl//0
    {6,{{6,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD},{35,BOND::SINGLE}},"C.ar",true,true,0},///Aromatic C- Br//0
    {6,{{6,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD},{53,BOND::SINGLE}},"C.ar",true,true,0},///Aromatic C- I//0
    {6,{{7,BOND::AROMATIC_BD},{7,BOND::AROMATIC_BD}},"C.ar",true,true,0}, /// 00A:C2//0
    {6,{{6,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD},{16,BOND::SINGLE}},"C.ar",true,true,0},///00I:C2//0
    {6,{{6,BOND::AROMATIC_BD},{7,BOND::AROMATIC_BD},{16,BOND::SINGLE}},"C.ar",true,true,0},///00N:C1X//0
    {6,{{7,BOND::AROMATIC_BD},{16,BOND::SINGLE}},"C.ar",true,true,0},///013:C4//0
    {6,{{6,BOND::AROMATIC_BD},{7,BOND::AROMATIC_BD},{17,BOND::SINGLE}},"C.ar",true,true,0},///087:C21//0
    {6,{{6,BOND::AROMATIC_BD},{7,BOND::AROMATIC_BD},{9,BOND::SINGLE}},"C.ar",true,true,0},///1RP:CAP//0
    {6,{{6,BOND::AROMATIC_BD},{7,BOND::AROMATIC_BD},{35,BOND::SINGLE}},"C.ar",true,true,0},///BrC(C)=N//0
    {6,{{6,BOND::SINGLE},{7,BOND::DOUBLE},{53,BOND::SINGLE}},"C.ar",true,true,0},///IC(C)=N//0
    {6,{{6,BOND::AROMATIC_BD},{7,BOND::AROMATIC_BD},{53,BOND::SINGLE}},"C.ar",true,true,0},///IC(C)=N//0
    {6,{{6,BOND::DOUBLE},{7,BOND::SINGLE},{35,BOND::SINGLE}},"C.ar",true,true,0},///08Y:C29//0
    {6,{{6,BOND::SINGLE},{7,BOND::DOUBLE},{35,BOND::SINGLE}},"C.ar",true,true,0},///09R:C5 (Same as 08Y:C29)//0
    {6,{{6,BOND::AROMATIC_BD},{7,BOND::AROMATIC_BD},{35,BOND::SINGLE}},"C.ar",true,true,0},///08Y:C29//0
    {6,{{6,BOND::SINGLE},{8,BOND::DOUBLE},{1,BOND::SINGLE}},"C.2",false,false,0},///0AO:C2//0
    {6,{{6,BOND::DOUBLE},{35,BOND::SINGLE},{35,BOND::SINGLE}},"C.2",false,false,0},/// BrC(Br)=C(C)C//0
    {6,{{6,BOND::DOUBLE},{9,BOND::SINGLE},{9,BOND::SINGLE}},"C.2",false,false,0},/// FC(F)=C(C)C//0
    {6,{{6,BOND::DOUBLE},{53,BOND::SINGLE},{53,BOND::SINGLE}},"C.2",false,false,0},/// IC(I)=C(C)C//0
    {6,{{8,BOND::TRIPLE}},"C.1",false,false,0},/// CMO
    {6,{{7,BOND::SINGLE},{7,BOND::DOUBLE},{1,BOND::SINGLE}},"C.ar",true,true,0},///3429401
    {6,{{6,BOND::TRIPLE},{35,BOND::SINGLE}},"C.1",false,false,0},
    {6,{{5,BOND::SINGLE},{5,BOND::SINGLE},{5,BOND::SINGLE},{5,BOND::SINGLE},{5,BOND::SINGLE},{6,BOND::SINGLE}},"C.3",false,false,2},///10R
    {6,{{5,BOND::SINGLE},{5,BOND::SINGLE},{5,BOND::SINGLE},{5,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::SINGLE}},"C.3",true,false,2},///25X
    {6,{{5,BOND::SINGLE},{5,BOND::SINGLE},{5,BOND::SINGLE},{5,BOND::SINGLE},{6,BOND::SINGLE}},"C.3",true,false,2},///25X
    {6,{{5,BOND::SINGLE},{5,BOND::SINGLE},{5,BOND::SINGLE},{5,BOND::SINGLE},{5,BOND::SINGLE}},"C.3",true,false,2},///25Y
    {6,{{5,BOND::SINGLE},{5,BOND::SINGLE},{5,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::SINGLE}},"C.3",true,false,2},///26E
    {6,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{46,BOND::SINGLE}},"C.3",true,false,0},///C4R:C12/C11
    {6,{{26,BOND::SINGLE},{26,BOND::SINGLE},{26,BOND::SINGLE},{26,BOND::SINGLE},{26,BOND::SINGLE},{26,BOND::SINGLE}},"C.3",true,false,3},///ICG
    {6,{{1,BOND::SINGLE},{5,BOND::SINGLE},{5,BOND::SINGLE},{5,BOND::SINGLE},{6,BOND::SINGLE},{27,BOND::SINGLE}},"C.3",true,false,2},///CB5
    {6,{{7,BOND::TRIPLE},{27,BOND::SINGLE}},"C.1",false,false,0},///CNC
    {6,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{76,BOND::SINGLE}},"C.2",true,false,0},///DWC
    {6,{{8,BOND::TRIPLE},{76,BOND::SINGLE}},"C.1",false,false,0},///DWC
    {6,{{8,BOND::TRIPLE},{75,BOND::SINGLE}},"C.1",false,false,0},///RCS
    {6,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{75,BOND::SINGLE}},"C.2",false,false,0},///RCS
    {6,{{6,BOND::DOUBLE},{78,BOND::SINGLE}},"C.2",true,false,0},///ZPT
    {6,{{6,BOND::DOUBLE},{6,BOND::DOUBLE},{44,BOND::SINGLE}},"C.2",true,false,0},///HB1:C5
    {6,{{1,BOND::SINGLE},{7,BOND::DOUBLE},{16,BOND::SINGLE}},"C.2",false,false,0},///31E:NAG
    {6,{{1,BOND::SINGLE},{7,BOND::SINGLE},{16,BOND::DOUBLE}},"C.2",false,false,0},///1ZW
    {6,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{6,BOND::SINGLE},{76,BOND::SINGLE}},"C.2",true,false,0},///1MK
    {6,{{6,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::DOUBLE},{26,BOND::SINGLE}},"C.2",true,false,1},///FEM
    {6,{{5,BOND::SINGLE},{5,BOND::SINGLE},{5,BOND::SINGLE},{5,BOND::SINGLE}},"C.3",false,false,0},///34B
    {6,{{5,BOND::SINGLE},{5,BOND::SINGLE},{5,BOND::SINGLE},{5,BOND::SINGLE},{1,BOND::SINGLE},{6,BOND::SINGLE}},"C.3",false,false,2},///34B
    {6,{{5,BOND::SINGLE},{5,BOND::SINGLE},{5,BOND::SINGLE}},"C.3",true,false,0},///DGQ
    {6,{{5,BOND::SINGLE},{5,BOND::SINGLE},{5,BOND::SINGLE},{5,BOND::SINGLE},{6,BOND::SINGLE}},"C.3",true,false,0},///DGQ
    {6,{{1,BOND::SINGLE},{5,BOND::SINGLE},{5,BOND::SINGLE},{5,BOND::SINGLE},{6,BOND::SINGLE}},"C.3",false,false,0},///39B
    {6,{{1,BOND::SINGLE},{6,BOND::SINGLE},{7,BOND::AROMATIC_BD}},"C.2",false,false,0},///4PO
    {6,{{1,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::DOUBLE},{77,BOND::SINGLE}},"C.2",true,true,0},///4IR
    {6,{{6,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::DOUBLE},{77,BOND::SINGLE}},"C.2",true,true,0},///4IR
    {6,{{6,BOND::SINGLE},{0,BOND::SINGLE},{0,BOND::DOUBLE}},"C.2",false,false,0},///ASX
    {6,{{1,BOND::SINGLE},{7,BOND::TRIPLE}},"C.2",false,false,0},///CN . ENC
    {6,{{1,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::DOUBLE},{76,BOND::SINGLE}},"C.2",true,true,0},///ELJ
    {6,{{1,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::DOUBLE},{26,BOND::SINGLE}},"C.2",true,true,0},///FEM
    {6,{{5,BOND::SINGLE},{5,BOND::SINGLE},{5,BOND::SINGLE},{5,BOND::SINGLE},{6,BOND::SINGLE}},"C.3",true,true,0},///34B
    {6,{{5,BOND::SINGLE},{5,BOND::SINGLE},{5,BOND::SINGLE},{6,BOND::SINGLE}},"C.3",true,false,0},///39B
    {6,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{16,BOND::SINGLE}},"C.ar",true,true,0},///0F2:CAW
    {6,{{7,BOND::SINGLE},{8,BOND::DOUBLE},{16,BOND::SINGLE}},"C.2",true,true,0},///0F5:C17
    {6,{{7,BOND::DOUBLE},{16,BOND::DOUBLE},{16,BOND::SINGLE}},"C.2",true,false,0},///0TE:C12
    {6,{{6,BOND::SINGLE},{7,BOND::SINGLE},{16,BOND::DOUBLE}},"C.2",true,false,0},///1MK:C6
    {6,{{7,BOND::SINGLE},{7,BOND::SINGLE},{16,BOND::DOUBLE}},"C.2",false,false,0},///2PT:C7
    {6,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{45,BOND::SINGLE}},"C.2",true,false,0},///C4R:C12
    {6,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{6,BOND::SINGLE}},"C.2",true,false,0},///4KV:C1
    {6,{{6,BOND::DOUBLE},{7,BOND::DOUBLE}},"C.2",true,true,0},///IZ3:C4
    {6,{{7,BOND::SINGLE},{16,BOND::TRIPLE}},"C.1",false,false,0},///OSV:C
    {6,{{1,BOND::SINGLE},{8,BOND::TRIPLE},{44,BOND::SINGLE}},"C.1",false,false,0},///ME3:C4
    {6,{{1,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::SINGLE},{77,BOND::SINGLE}},"C.3",true,true,0},///4IR:C18
    {6,{{1,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::DOUBLE},{75,BOND::SINGLE}},"C.2",true,true,0},///RCS:C1
    {6,{{1,BOND::SINGLE},{1,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::SINGLE},{8,BOND::SINGLE}},"C.3",true,false,0},///VV7:C4
    {6,{{1,BOND::SINGLE},{6,BOND::SINGLE},{16,BOND::DOUBLE}},"C.2",true,true,0},///V78:CXT
    {6,{{8,BOND::TRIPLE},{75,BOND::SINGLE}},"C.1",false,false,0},///REI:C1
    {6,{{1,BOND::SINGLE},{6,BOND::DOUBLE},{78,BOND::SINGLE}},"C.2",true,true,0},///ZPT:C1
    {6,{{1,BOND::SINGLE},{1,BOND::SINGLE},{6,BOND::DOUBLE},{78,BOND::SINGLE}},"C.2",true,true,0},///ZPT:C1
    {6,{{6,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::DOUBLE},{77,BOND::SINGLE}},"C.2",true,true,0},///ZPT:C1
    {6,{{1,BOND::SINGLE},{1,BOND::SINGLE},{6,BOND::DOUBLE},{6,BOND::SINGLE},{8,BOND::SINGLE}},"C.3",false,false,1},///ZPT:C1
    {6,{{6,BOND::SINGLE},{7,BOND::AMIDE},{8,BOND::DOUBLE}},"C.2",false,false,0},///2r59:C11
    {6,{{6,BOND::SINGLE},{6,BOND::AROMATIC_BD},{7,BOND::AROMATIC_BD}},"C.ar",true,true,0},
    {6,{{6,BOND::AROMATIC_BD},{7,BOND::AROMATIC_BD},{7,BOND::AROMATIC_BD}},"C.ar",true,true,0},
    {6,{{6,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD},{7,BOND::SINGLE}},"C.ar",true,true,0},
    {6,{{35,BOND::SINGLE},{8,BOND::DOUBLE},{6,BOND::SINGLE}},"C.2",false,false,0},
    {6,{{6,BOND::SINGLE},{8,BOND::DOUBLE},{7,BOND::SINGLE}},"C.2",false,false,0},///Amide
    {6,{{7,BOND::SINGLE},{7,BOND::SINGLE},{16,BOND::DOUBLE}},"C.2",true,false,0},
    {6,{{5,BOND::SINGLE},{5,BOND::SINGLE},{5,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::SINGLE}},"C.3",true,false,2},//OY8:C2
    {6,{{5,BOND::SINGLE},{5,BOND::SINGLE},{5,BOND::SINGLE},{6,BOND::SINGLE}},"C.3",true,false,1},//OY8:C1
    {6,{{7,BOND::SINGLE},{7,BOND::SINGLE},{16,BOND::DOUBLE}},"C.2",true,false,0},//6WF:C02|8ZR




};

bool protspace::AtomPerception::processCarbon(MMAtom& atom)
{

    mAtStat.updateAtom(atom);
    try{
        if (mGroupPerception){
            if (isCarboxylicAcid(atom)) return true;
            if (isGuanidinium(atom)) return true;
            }
            if (isAmidinium(atom))return true;
            if (isAmide(atom))return true;
            if (isCarbamoyl(atom))return true;
            return isAmideTwoNitro(atom);
    }catch(ProtExcept &e)
    {
        e.addHierarchy("AtomPerc::processCarbon");

        throw;
    }
}



bool protspace::AtomPerception::isCarboxylicAcid(MMAtom &atom)

try{

    if (mAtStat.numBond()!=3)return false;
    if (mAtStat.numOx()!=2) return false;
    if (!(mAtStat.numDouble()==1 &&mAtStat.numSing()==2)
     && !(mAtStat.numSing()==1 && mAtStat.numDe()==2)
     && !(mAtStat.numSing()==1 && mAtStat.numDouble()==2))
      return false;

    if ( (  mAtStat.resType() == RESTYPE::STANDARD_AA
            ||mAtStat.resType() == RESTYPE::MODIFIED_AA)
         && atom.getName()=="C" )
    {
        bool isTerminal =false;
        for (size_t i=0;i<3;++i)
        {
            if (!atom.getAtom(i).isOxygen())continue;
            if (atom.getAtom(i).getName()=="OXT")isTerminal=true;
        }
        if (!isTerminal)return false;
    }
    bool find =false;
    for (size_t i=0;i<3;++i)
    {
        if (!atom.getAtom(i).isOxygen())continue;
        const uint16_t& type=atom.getBond(i).getType();
        if (type==BOND::DOUBLE||type==BOND::DELOCALIZED)find=true;
        if (protspace::numHeavyAtomBonded(atom.getAtom(i))>1)return false;
    }
    if (!find)return false;
    MMAtom* O1=nullptr, *O2=nullptr, *OTH=nullptr;
    for (size_t i=0;i<3;++i)
    {
        MMAtom& atomL1 = atom.getAtom(i);
        if (!atomL1.isOxygen())OTH=&atomL1;
        else if (O1==nullptr) O1=&atomL1;
        else if (O2==nullptr) O2=&atomL1;
    }

    if (O1==nullptr || O2 ==nullptr || OTH==nullptr)return false;
    if (numHeavyAtomBonded(*O1)!=1)return false;
    if (numHeavyAtomBonded(*O2)!=1)return false;
    if (numHydrogenAtomBonded(*O1)!=0)removeAllHydrogen(*O1);
    if (numHydrogenAtomBonded(*O2)!=0)removeAllHydrogen(*O2);

    setMOL2(atom,"C.2");setMOL2(*O1,"O.co2"); setMOL2(*O2,"O.co2");
    atom.setFormalCharge(-1);
    O1->setFormalCharge(0);
    O2->setFormalCharge(0);
//std::cout <<atom.getResidue().getName()<<"\t"<<atom.getName()<<"\t"<<"CARBO"<<std::endl;
//std::cout <<O1->getResidue().getName()<<"\t"<<O1->getName()<<"\t"<<"CARBO"<<std::endl;
//std::cout <<O2->getResidue().getName()<<"\t"<<O2->getName()<<"\t"<<"CARBO"<<std::endl;

    atom.getBond(*O1).setBondType(BOND::AROMATIC_BD);
    atom.getBond(*O2).setBondType(BOND::AROMATIC_BD);
    atom.getBond(*OTH).setBondType(BOND::SINGLE);
    return true;
}catch(ProtExcept &e)
{
    e.addHierarchy("AtomPerception::isCarboxylicAcid");
    throw;
}





bool protspace::AtomPerception::isAmidinium(MMAtom &atom)
{
    try{
    if (mAtStat.numBond()!=3||mAtStat.numN()!=2||
            mAtStat.numC()!=1||mAtStat.numDouble()!=1||mAtStat.numSing()!=2)return false;
    bool find =false;
    for (size_t i=0;i<3;++i)
    {
        if (!atom.getAtom(i).isNitrogen())continue;
        if (atom.getBond(i).getType()==BOND::DOUBLE)find=true;
        if (protspace::numHeavyAtomBonded(atom.getAtom(i))>1)return false;
    }
        if (!find)return false;
    for (size_t i=0;i<3;++i)
    {
        MMAtom& atomL1 = atom.getAtom(i);
        if (!atomL1.isNitrogen()) continue;

       // std::cout <<atomL1.getResidue().getName()<<"\t"<<atomL1.getName()<<"\t"<<"AMIDINIUM"<<std::endl;
        setMOL2(atomL1,"N.pl3");
    }
   // std::cout <<atom.getResidue().getName()<<"\t"<<atom.getName()<<"\t"<<"AMIDINIUM"<<std::endl;
    setMOL2(atom,"C.2");
    return true;
}catch(ProtExcept &e)
    {
 e.addHierarchy("AtomPerception::isAmidinium");
 throw;
    }
}

bool protspace::AtomPerception::isAmide(MMAtom& atom)
{
    try{
        if (!(mAtStat.numC()  >=1 &&
              mAtStat.numOx() >=1 &&
              mAtStat.numN()  ==1 &&
              mAtStat.numBd() ==3))return false;
        MMAtom* N1=nullptr, *O1=nullptr;

        for (size_t i=0;i<3;++i)
        {

            MMAtom& atomL1 = atom.getAtom(i);
            const unsigned short& btype= atom.getBond(i).getType();
            if (atomL1.isOxygen() && btype==BOND::DOUBLE) O1=&atomL1;
            if (atomL1.isNitrogen() && btype==BOND::SINGLE) N1=&atomL1;
        }
        if (N1==nullptr || O1==nullptr)return false;

        atom.getBond(*N1).setBondType(BOND::AMIDE);

        const size_t NH1=numHydrogenAtomBonded(*N1);
        if (N1->numBonds()-NH1==1   && NH1==1)// CASE ASN - To check
            setMOL2(*N1,"N.pl3");
        else setMOL2(*N1,"N.am");

        O1->getBond(atom).setBondType(BOND::DOUBLE);
        setMOL2(atom,"C.2"); setMOL2(*O1,"O.2");
      //  std::cout <<atom.getResidue().getName()<<"\t"<<atom.getName()<<"\t"<<"AMIDE"<<std::endl;
      //  std::cout <<O1->getResidue().getName()<<"\t"<<O1->getName()<<"\t"<<"AMIDE"<<std::endl;
      //  std::cout <<N1->getResidue().getName()<<"\t"<<N1->getName()<<"\t"<<"AMIDE"<<std::endl;

        return true;
    }catch(ProtExcept &e)
    {
        e.addHierarchy("AtomPerc::isAmide");
        throw;
    }
}



bool protspace::AtomPerception::isGuanidinium(MMAtom&atom)
{
    try{

        if(!(mAtStat.numN()==3 &&
             mAtStat.numBd() == 3 &&
             mAtStat.numHy()==0&&
             !mAtStat.isRing()))return false;

        if (!(mAtStat.numDouble()==1&&mAtStat.numSing()==2)&&
            !(mAtStat.numDe()==3))return false;

        size_t nNLinkC=0;
        for(size_t i=0;i<3;++i)
        {
            MMAtom& atomL1 = atom.getAtom(i);
            if (protspace::numHeavyAtomBonded(atomL1)>1)nNLinkC++;
        }
        if (nNLinkC != 1)return false;
        setMOL2(atom,"C.cat");atom.setFormalCharge(1);
       // std::cout <<atom.getResidue().getName()<<"\t"<<atom.getName()<<"\t"<<"GUAD"<<std::endl;
        for (size_t i=0;i<3;++i)
        {
            MMAtom& atomL1 = atom.getAtom(i);
            setMOL2(atomL1,"N.pl3");
            atomL1.getBond(atom).setBondType(BOND::AROMATIC_BD);
//std::cout <<atomL1.getResidue().getName()<<"\t"<<atomL1.getName()<<"\t"<<"GUAD"<<std::endl;
        }

        return true;
    }catch(ProtExcept &e)
    {
        e.addHierarchy("AtomPerc::isGuanidinium");
        throw;
    }
}





bool protspace::AtomPerception::isAmideTwoNitro(MMAtom& atom)
{
    try{
        if (!(mAtStat.numN() ==2 &&
              mAtStat.numOx()==1 &&
              mAtStat.numBd()==3))return false;

        // CASE 046:C16
        bool isOxDbBond=false, isNSingBond=false;
        MMAtom *N1=nullptr, *N2=nullptr, *O1=nullptr;


        for (size_t i=0;i<3;++i)
        {
            MMAtom& atomL1 = atom.getAtom(i);
            const unsigned short& btype= atom.getBond(i).getType();
            if (atomL1.isOxygen() && btype==BOND::DOUBLE){ O1=&atomL1;isOxDbBond=true;}
            if (atomL1.isNitrogen() && btype==BOND::SINGLE){
                if (N1==NULL)N1=&atomL1;else N2=&atomL1;
                isNSingBond=true;}
        }

        if (!isOxDbBond || !isNSingBond)return false;
        if (N1==(MMAtom*)NULL || N2==(MMAtom*)NULL) return false;

        atom.getBond(*N1).setBondType(BOND::AMIDE);
        setMOL2(atom,"C.2");setMOL2(*O1,"O.2");
        //std::cout <<atom.getResidue().getName()<<"\t"<<atom.getName()<<"\t"<<"AMIDE2NITRO"<<std::endl;
        //std::cout <<N1->getResidue().getName()<<"\t"<<N1->getName()<<"\t"<<"AMIDE2NITRO"<<std::endl;
       // std::cout <<N2->getResidue().getName()<<"\t"<<N2->getName()<<"\t"<<"AMIDE2NITRO"<<std::endl;
       // std::cout <<O1->getResidue().getName()<<"\t"<<O1->getName()<<"\t"<<"AMIDE2NITRO"<<std::endl;
        if (!N1->getParent().isAtomInRing(*N1))    setMOL2(*N1,"N.am");
        else if (!protspace::isAtomInAromRing(*N1))    setMOL2(*N1,"N.am");


        if (!N2->getParent().isAtomInRing(*N2))    setMOL2(*N2,"N.am");
        else if (!protspace::isAtomInAromRing(*N2))setMOL2(*N2,"N.am");

        return true;
    }catch(ProtExcept &e)
    {
        e.addHierarchy("AtomPerc::isAmideTwoNitro");
        throw;
    }


}
bool protspace::AtomPerception::isCarbamoyl(MMAtom& pAtom)
{//Case 04V:C1
    try{
        if (!(mAtStat.numOx()==1 &&
              mAtStat.numN()==1 &&
              mAtStat.numC()==1 &&
              mAtStat.numBd()==3 &&
              mAtStat.numSing()==2 &&
              mAtStat.numDouble()==1))return false;

        const size_t CPos = protspace::hasAtom(pAtom,6,BOND::SINGLE,0);
        const size_t NPos = protspace::hasAtom(pAtom,7,BOND::DOUBLE,0);
        const size_t OPos = protspace::hasAtom(pAtom,8,BOND::SINGLE,0);
        if (CPos == pAtom.numBonds()||NPos==pAtom.numBonds()||OPos==pAtom.numBonds())return false;
        MMAtom& ox=pAtom.getAtom(OPos); if (protspace::numHeavyAtomBonded(ox)!=1)return false;
        MMAtom& n=pAtom.getAtom(NPos); if (protspace::numHeavyAtomBonded(n)!=1)return false;
       // std::cout <<pAtom.getResidue().getName()<<"\t"<<pAtom.getName()<<"\t"<<"CARBAMOYL"<<std::endl;
       // std::cout <<ox.getResidue().getName()<<"\t"<<ox.getName()<<"\t"<<"CARBAMOYL"<<std::endl;
       // std::cout <<n.getResidue().getName()<<"\t"<<n.getName()<<"\t"<<"CARBAMOYL"<<std::endl;
        setMOL2(pAtom,"C.2");
        setMOL2(ox,"O.2"); pAtom.getBond(ox).setBondType(BOND::DOUBLE);
        setMOL2(n,"N.pl3");pAtom.getBond(n).setBondType(BOND::SINGLE);
        return true;
    }catch(ProtExcept &e)
    {
        e.addHierarchy("AtomPerc::isNitro");
        throw;
    }
}



bool protspace::AtomPerception::perceiveSingleCarbon(MMAtom& atom)
{
    try{
        if (!mAtStat.isArRing() &&
                mAtStat.numAr()==0  &&
                mAtStat.numDouble()==0  &&
                mAtStat.numTriple()==0 && mAtStat.numBd()<=4)
        {
            //std::cout <<atom.getResidue().getName()<<"\t"<<atom.getName()<<"\t"<<"CARBON_0"<<std::endl;
            setMOL2(atom,"C.3");
            return true;
        }
        if (mAtStat.numBd()==2 && mAtStat.numSing()==1 && mAtStat.numTriple()==1)
        {
            setMOL2(atom,"C.1");
            return true;
        }
        if (mAtStat.isArRing()) {setMOL2(atom,"C.ar");
            //std::cout <<atom.getResidue().getName()<<"\t"<<atom.getName()<<"\t"<<"CARBON_1"<<std::endl;
            return true;}

        size_t pos=0;
        for(const AtomRule& entry:mCarbonRules)
        {++pos;
            ++mAllCounts;
            if (!followRule(entry,atom))continue;
            //std::cout <<atom.getResidue().getName()<<"\t"<<atom.getName()<<"\t"<<"CR_"<<pos-1<<std::endl;
            setMOL2(atom,entry.mMOL2);
            mCountsC.at(pos-1)++;
            return true;
        }


        /// Manual correction to apply to IZ3
        /// Ignore R1A
        ///3164027 covered by 0US

        LOG_ERR("Unrecognized atom "+atom.getParent().getName()+" - "+atom.getIdentifier()+"\n"+atom.toString());
        atom.setMOL2Type("Du");
        return false;


    }catch(ProtExcept &e)
    {
        e.addHierarchy("AtomPerc::perceiveSingleCarbon");
        throw;
    }
}


