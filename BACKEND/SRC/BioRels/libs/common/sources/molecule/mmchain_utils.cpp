#include <sstream>
#include "headers/molecule/mmchain_utils.h"
#include "headers/molecule/mmresidue_utils.h"
#include "headers/molecule/mmresidue.h"
#include "headers/molecule/mmatom.h"
#undef NDEBUG /// Active assertion in release
namespace protspace {
void getChainComposition(const MMChain& pChain,std::map<uint16_t,double>& pStart)
{
    pStart.clear();
    for(size_t i=0;i<NB_RESTYPE;++i)
    {
        uint16_t val;
        switch (i)
        {
        case 0 : val=RESTYPE::UNDEFINED    ;break;
        case 1: val=RESTYPE::STANDARD_AA  ;break;
        case 2: val=RESTYPE::MODIFIED_AA  ;break;
        case 3: val=RESTYPE::NUCLEIC_ACID ;break;
        case 4: val=RESTYPE::WATER        ;break;
        case 5: val=RESTYPE::LIGAND       ;break;
        case 6: val=RESTYPE::SUGAR        ;break;
        case 7: val=RESTYPE::ORGANOMET    ;break;
        case 8: val=RESTYPE::METAL        ;break;
        case 9: val=RESTYPE::COFACTOR     ;break;
        case 10: val=RESTYPE::ION          ;break;
        case 11: val=RESTYPE::PROSTHETIC   ;break;
        case 12: val=RESTYPE::UNWANTED     ;break;
        }
        pStart.insert(std::make_pair(val,0));
    }
    for(size_t iRes=0;iRes < pChain.numResidue();++iRes)
    {
        pStart[pChain.getResidue(iRes).getResType()]++;
//        cout << pChain.getResidue(iRes).getIdentifier()<<"\t"<<(unsigned)pChain.getResidue(iRes).getResType()
//             <<"\t"<<pStart[pChain.getResidue(iRes).getResType()]<<endl;
    }
}



Coords getGeomCenter(const MMChain& pChain)
{
    Coords sum; double n=0;
    for(size_t iRes=0;iRes < pChain.numResidue();++iRes)
    {

      const MMResidue& res = pChain.getResidue(iRes);
      for(size_t iAtm=0;iAtm < res.numAtoms();++iAtm)
      {
          const MMAtom& atom = res.getAtom(iAtm);
          if (atom.isHydrogen())continue;
          sum+=atom.pos();
          ++n;
      }
    }
    if (n==0)return 0;
    return sum/n;
}

std::string toFASTA(const MMChain& pChain ,const bool& wHeader)
{
    std::ostringstream sequence;
    if (wHeader)
    {
        sequence<< ">"<<pChain.getMoleName()+"|"+pChain.getName()+"\n";
    }
    for (size_t iRes=0;iRes < pChain.numResidue();++iRes)
    {
        const std::string& Rname=pChain.getResidue(iRes).getName();
        sequence<<residue3Lto1L(Rname);
    }
    return sequence.str();

}
}
