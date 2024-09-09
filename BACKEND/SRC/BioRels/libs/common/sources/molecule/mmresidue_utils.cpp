#include <math.h>
#include "headers/molecule/mmresidue_utils.h"
#include "headers/statics/intertypes.h"
#include "headers/statics/logger.h"
#include "headers/molecule/mmresidue.h"
#include "headers/statics/residuedata.h"
#include "headers/molecule/mmchain.h"
#include "headers/molecule/macromole.h"
#include "headers/molecule/mmatom.h"
#include "headers/math/coords_utils.h"
#include "headers/statics/intertypes.h"
#undef NDEBUG /// Active assertion in release
using namespace std;

namespace protspace
{



const std::string& residue1Lto3L(const std::string& letter)
{
    if (letter.length()!=1)
        throw_line("325001",
                   "residue1Lto3L",
                   "input should be 1 letter");
    for (size_t iAA=0; iAA <NBAA; ++iAA)
    {
        if (AAcid[iAA].code!=letter) continue;
        return AAcid[iAA].name;

    }
    throw_line("325002",
               "residue1Lto3L",
               "Name not found");
}





std::string residue3Lto1L(const std::string& name)
{
    for (size_t iAA=0; iAA <NBAA; ++iAA)
    {
        if (AAcid[iAA].name!=name) continue;
        return AAcid[iAA].code;

    }
    return "X";
}






void perceiveClass(MMResidue& residue,const std::string& monomer_type)
{

    unsigned short nOx=0,
            nHyd=0,
            nCarb=0,
            nUnBiol=0,
            nMet=0,
            nBiol=0,
            nHal=0,
            nIon=0;

    const size_t nAtom=residue.numAtoms();
    for (size_t iAtm=0;iAtm < nAtom;++iAtm)
    {
        const MMAtom& atom=residue.getAtom(iAtm);
        if (atom.isCarbon())nCarb++;
        else if (atom.isOxygen())nOx++;
        else if (atom.isHydrogen())nHyd++;
        else if (atom.isMetallic())nMet++;
        else if (atom.isHalogen())nHal++;
        else if (atom.isIon())nIon++;
        if (atom.isBioRelevant())nBiol++;else nBiol++;
    }
    bool found=false;
    for (size_t i=0;i< NBAA;++i)
    {
        if (AAcid[i].name!=residue.getName()) continue;
        found=true;break;
    }
    if (found)
    {

        residue.setResidueType(RESTYPE::STANDARD_AA);
    }
    else if (monomer_type == "DNA LINKING"
             ||monomer_type == "RNA LINKING")
    {
        residue.setResidueType(RESTYPE::NUCLEIC_ACID);
    }
    else if (nHyd==2 && nOx==1 && nAtom==3)
    {
        residue.setResidueType(RESTYPE::WATER);
    }
    else if ((nHal==1||nIon==1) && nAtom ==1)
    {
        residue.setResidueType(RESTYPE::ION);
    }
    else if (nMet==1 && nAtom==1)
    {
        residue.setResidueType(RESTYPE::METAL);
    }
    else if ((nMet>=1||nUnBiol>=1) && nAtom>=1)
    {
        residue.setResidueType(RESTYPE::ORGANOMET);
    }
    else if (nBiol ==1 && nAtom )
        residue.setResidueType(RESTYPE::UNWANTED);
    else residue.setResidueType(RESTYPE::LIGAND);
}



const std::string& getResidueType(const MMResidue& residue)
{
    const auto it=RESTYPE::typeToName.find(residue.getResType());
    if (it == RESTYPE::typeToName.end())
        throw_line("325101","getResidueType",
                   "Unrecognized residue type");
    return (*it).second;
}





double getWeight(const MMResidue& residue)
{
    double weight=0;
    for (size_t iAtm=0; iAtm< residue.numAtoms();++iAtm)
    {
        const MMAtom& atom=residue.getAtom(iAtm);
        weight+=atom.getWeigth();
    }
    return weight;
}







void getLinkedResidue(const MMResidue& residue,
                      std::vector<MMBond*>& list)
{

    for (size_t iAtm=0; iAtm< residue.numAtoms();++iAtm)
    {
        const MMAtom& atom=residue.getAtom(iAtm);
        if (atom.isHydrogen())continue;
        for (size_t iBd=0; iBd < atom.numBonds();++iBd)
        {
            const MMAtom& atom2= atom.getAtom(iBd);
            if (&atom2.getResidue() != &residue) list.push_back(&atom.getBond(iBd));
        }
    }
}






void getLinkedResidue(const MMResidue& residue,
                      std::vector<MMResidue*>& list)
{

    for (size_t iAtm=0; iAtm< residue.numAtoms();++iAtm)
    {
        const MMAtom& atom=residue.getAtom(iAtm);
        if (atom.isHydrogen())continue;
        for (size_t iBd=0; iBd < atom.numBonds();++iBd)
        {
            const MMAtom& atom2= atom.getAtom(iBd);
            if (&atom2.getResidue() != &residue) list.push_back(&atom2.getResidue());
        }
    }
}





short nLinkedResidue(const MMResidue& residue)
{
    short nLinkRes=0;
    for (size_t iAtm=0; iAtm< residue.numAtoms();++iAtm)
    {
        const MMAtom& atom=residue.getAtom(iAtm);
        if (atom.isHydrogen())continue;
        for (size_t iBd=0; iBd < atom.numBonds();++iBd)
        {
            const MMAtom& atom2= atom.getAtom(iBd);
            if (&atom2.getResidue() != &residue) nLinkRes++;
        }
    }
    return nLinkRes;
}

bool isAA(const MMResidue& pRes)
{
    return (pRes.getResType()==RESTYPE::STANDARD_AA || pRes.getResType()==RESTYPE::MODIFIED_AA);
}




unsigned short numHeavyAtom(const MMResidue& residue)
{
    unsigned short nAtm=0;
    for (size_t iAtm=0; iAtm< residue.numAtoms();++iAtm)
    {
        const MMAtom& atom=residue.getAtom(iAtm);
        if (!atom.isHydrogen()) nAtm++;
    }
    return nAtm;
}








double getShortestDistance(const MMResidue& res1,const MMResidue& res2,const double& thres)
{
    double max=10000;
    const double thres_squared= thres*thres;
    for(size_t i=0;i<res1.numAtoms();++i)
    {
        const MMAtom& atom1=res1.getAtom(i);
        if (atom1.isHydrogen())continue;
        const Coords& pos1=atom1.pos();
        for(size_t j=0;j<res2.numAtoms();++j)
        {
            const MMAtom& atom2=res2.getAtom(j);
            if (atom2.isHydrogen())continue;
            const Coords& pos2=atom2.pos();
            const double distsq=pos1.distance_squared(pos2);
            if (distsq < max) max=distsq;
            if (max < thres_squared) return sqrt(max);
        }
    }
    return sqrt(max);
}










double getShortestDistance(const MMAtom& atom1,const MMResidue& res2,const double& thres)
{
    const double thres_squared(thres*thres);
    double max=1000;
    const Coords& pos1=atom1.pos();
    for(size_t j=0;j<res2.numAtoms();++j)
    {
        const MMAtom& atom2=res2.getAtom(j);
        if (atom2.isHydrogen())continue;
        const Coords& pos2=atom2.pos();
        const double dist2(pos1.distance_squared(pos2));
        if (dist2<max)max=dist2;
        if (max < thres_squared) return sqrt(max);
    }
    return sqrt(max);
}








double getAverageDistance(const MMResidue& res1,
                          const MMResidue& res2)
{
    double avg=0,n=0;
    for(size_t i=0;i<res1.numAtoms();++i)
    {
        const MMAtom& atom1=res1.getAtom(i);
        if (atom1.isHydrogen())continue;
        const Coords& pos1=atom1.pos();
        for(size_t j=0;j<res2.numAtoms();++j)
        {
            const MMAtom& atom2=res2.getAtom(j);
            if (atom2.isHydrogen())continue;
            const Coords& pos2=atom2.pos();
            avg+=(pos1.distance(pos2));
            ++n;
        }
    }
    if (n==0)return 0;
    return avg/n;
}



bool isProteinChain(const MMChain& pChain)
{
    double mRType[NB_RESTYPE],tot=0;
    for(size_t i=0;i<NB_RESTYPE;++i)mRType[i]=0;
    for(size_t i=0;i<pChain.numResidue();++i)
    {
        const uint16_t& mType = pChain.getResidue(i).getResType();
        switch (mType)
        {
        case RESTYPE::UNDEFINED    : mRType[0]++;
        case RESTYPE::STANDARD_AA  : mRType[1]++;
        case RESTYPE::MODIFIED_AA  : mRType[2]++;
        case RESTYPE::NUCLEIC_ACID : mRType[3]++;
        case RESTYPE::WATER        : mRType[4]++;
        case RESTYPE::LIGAND       : mRType[5]++;
        case RESTYPE::SUGAR        : mRType[6]++;
        case RESTYPE::ORGANOMET    : mRType[7]++;
        case RESTYPE::METAL        : mRType[8]++;
        case RESTYPE::COFACTOR     : mRType[9]++;
        case RESTYPE::ION          : mRType[10]++;
        case RESTYPE::PROSTHETIC   : mRType[11]++;
        case RESTYPE::UNWANTED     : mRType[12]++;
        }
        ++tot;
    }
    if (tot <15) return false;
    if ((mRType[1]+mRType[2])/(tot-mRType[4]) <0.7) return false;
    return true;
}





double getAvgBFactor(const MMResidue& res)
{
    double sum=0,n=0;
    for(size_t i=0;i<res.numAtoms();++i)
    {
        const MMAtom& atom =res.getAtom(i);
        if (atom.isHydrogen())continue;
        ++n;sum+=atom.getBFactor();
    }
    if (n==0)return 0;
    else return sum/n;
}


bool getPHI(const MMResidue& pRes, double& val)
{
      size_t posN, posCA, posC;
      if (!pRes.getAtom("N", posN ))return false;
      if (!pRes.getAtom("CA",posCA))return false;
      if (!pRes.getAtom("C", posC ))return false;
      protspace::MMAtom& atomN=pRes.getAtom(posN);
      for(size_t i=0;i<atomN.numBonds();++i)
      {
          protspace::MMAtom& atomC=atomN.getAtom(i);
          if (atomC.getName()!="C")continue;
          val = computeSignedDihedralAngle(
                      atomC.pos(),
                      atomN.pos(),
                      pRes.getAtom(posCA).pos(),
                      pRes.getAtom(posC).pos());
          return true;
      }
      return false;

}

bool getPSI(const MMResidue& pRes, double& val)
{
      size_t posN, posCA, posC;
      if (!pRes.getAtom("N", posN ))return false;
      if (!pRes.getAtom("CA",posCA))return false;
      if (!pRes.getAtom("C", posC ))return false;
      protspace::MMAtom& atomC=pRes.getAtom(posC);
      for(size_t i=0;i<atomC.numBonds();++i)
      {
          protspace::MMAtom& atomN=atomC.getAtom(i);
          if (atomN.getName()!="N")continue;
          val = computeSignedDihedralAngle(
                      pRes.getAtom(posN).pos(),
                      pRes.getAtom(posCA).pos(),
                      atomC.pos(),
                      atomN.pos());
          return true;
      }
      return false;

}


bool getCHI1(const MMResidue& pRes, double& val)
{
    const std::string& pName = pRes.getName();
    static const std::vector<std::vector<std::string>> listAA={
        {"ARG","ASN","ASP","GLN","GLU","HIS","LEU","LYS","MET","PHE","PRO","TRP","TYR"},
        {"CYS"},
        {"ILE","VAL"},
        {"SER"},
        {"THR"}
    };
    static const std::vector<std::vector<std::string>> listATOM=
    {{"N","CA","CB","CG"},
     {"N","CA","CB","SG"},
     {"N","CA","CB","CG1"},
     {"N","CA","CB","OG"},
     {"N","CA","CB","OG1"}};
    for(size_t iAA=0;iAA< listAA.size();++iAA)
    {
        auto it= std::find(listAA.at(iAA).begin(),listAA.at(iAA).end(),pName);
        if (it == listAA.at(iAA).end())continue;
        try{
            val = computeSignedDihedralAngle(
                        pRes.getAtom(listATOM.at(iAA).at(0)).pos(),
                        pRes.getAtom(listATOM.at(iAA).at(1)).pos(),
                        pRes.getAtom(listATOM.at(iAA).at(2)).pos(),
                        pRes.getAtom(listATOM.at(iAA).at(3)).pos());

        }catch(ProtExcept &e)
        {
            return false;
        }
        return true;
    }
    return false;
}



bool getCHI2(const MMResidue& pRes, double& val)
{
    const std::string& pName = pRes.getName();
    static const std::vector<std::vector<std::string>> listAA={
        {"ARG","GLN","GLU","ILE","LYS","MET","PRO"},
        {"ASN","ASP"},
        {"HIS"},
        {"LEU","PHE","TRP","TYR"},
        {"MET"}
    };
    static const std::vector<std::vector<std::string>> listATOM=
    {{"CA","CB","CG","CD"},
     {"CA","CB","CG","OD1"},
     {"CA","CB","CG","ND1"},
     {"CA","CB","CG","CD1"},
     {"CA","CB","CG","SD"}};
    for(size_t iAA=0;iAA< listAA.size();++iAA)
    {
        auto it= std::find(listAA.at(iAA).begin(),listAA.at(iAA).end(),pName);
        if (it == listAA.at(iAA).end())continue;
        try{
            val = computeSignedDihedralAngle(
                        pRes.getAtom(listATOM.at(iAA).at(0)).pos(),
                        pRes.getAtom(listATOM.at(iAA).at(1)).pos(),
                        pRes.getAtom(listATOM.at(iAA).at(2)).pos(),
                        pRes.getAtom(listATOM.at(iAA).at(3)).pos());

        }catch(ProtExcept &e)
        {
            return false;
        }
        return true;
    }
    return false;
}

bool getCHI3(const MMResidue& pRes, double& val)
{
    const std::string& pName = pRes.getName();
    static const std::vector<std::vector<std::string>> listAA={
        {"ARG"},
        {"GLN","GLU"},
        {"LYS"},
        {"MET"},
    };
    static const std::vector<std::vector<std::string>> listATOM=
    {
        {"CB","CG","CD","NE"},
        {"CB","CG","CD","OE1"},
        {"CB","CG","CD","CE"},
        {"CB","CG","SD","CE"},
    };
    for(size_t iAA=0;iAA< listAA.size();++iAA)
    {
        auto it= std::find(listAA.at(iAA).begin(),listAA.at(iAA).end(),pName);
        if (it == listAA.at(iAA).end())continue;
        try{
            val = computeSignedDihedralAngle(
                        pRes.getAtom(listATOM.at(iAA).at(0)).pos(),
                        pRes.getAtom(listATOM.at(iAA).at(1)).pos(),
                        pRes.getAtom(listATOM.at(iAA).at(2)).pos(),
                        pRes.getAtom(listATOM.at(iAA).at(3)).pos());

        }catch(ProtExcept &e)
        {
            return false;
        }
        return true;
    }
    return false;
}



bool getCHI4(const MMResidue& pRes, double& val)
{
    const std::string& pName = pRes.getName();
    static const std::vector<std::vector<std::string>> listAA={
        {"ARG"},
        {"LYS"}
    };
    static const std::vector<std::vector<std::string>> listATOM=
    {
        {"CG","CD","NE","CZ"},
        {"CG","CD","CE","NZ"},
    };
    for(size_t iAA=0;iAA< listAA.size();++iAA)
    {
        auto it= std::find(listAA.at(iAA).begin(),listAA.at(iAA).end(),pName);
        if (it == listAA.at(iAA).end())continue;
        try{
            val = computeSignedDihedralAngle(
                        pRes.getAtom(listATOM.at(iAA).at(0)).pos(),
                        pRes.getAtom(listATOM.at(iAA).at(1)).pos(),
                        pRes.getAtom(listATOM.at(iAA).at(2)).pos(),
                        pRes.getAtom(listATOM.at(iAA).at(3)).pos());

        }catch(ProtExcept &e)
        {
            return false;
        }
        return true;
    }
    return false;
}




bool getCHI5(const MMResidue& pRes, double& val)
{
    const std::string& pName = pRes.getName();
    static const std::vector<std::vector<std::string>> listAA={
        {"ARG"},
    };
    static const std::vector<std::vector<std::string>> listATOM=
    {
        {"CD","NE","CZ","NH1"},

    };
    for(size_t iAA=0;iAA< listAA.size();++iAA)
    {
        auto it= std::find(listAA.at(iAA).begin(),listAA.at(iAA).end(),pName);
        if (it == listAA.at(iAA).end())continue;
        try{
            val = computeSignedDihedralAngle(
                        pRes.getAtom(listATOM.at(iAA).at(0)).pos(),
                        pRes.getAtom(listATOM.at(iAA).at(1)).pos(),
                        pRes.getAtom(listATOM.at(iAA).at(2)).pos(),
                        pRes.getAtom(listATOM.at(iAA).at(3)).pos());

        }catch(ProtExcept &e)
        {
            return false;
        }
        return true;
    }
    return false;
}


void checkResidueNumber(MacroMole& pMole)
{
    std::vector<int> list;
    for(size_t iChain=0;iChain <pMole.numChains();++iChain)
    {
        MMChain& pChain = pMole.getChain((const signed char &) iChain);
        const size_t nRes = pChain.numResidue();
        list.clear(); list.reserve(nRes);
        bool clear=true;
        for(size_t iRes=0;iRes < nRes;++iRes)
        {
            const int& resId=pChain.getResidue(iRes).getFID();
            const auto it = std::find(list.begin(),list.end(),resId);
            if (it == list.end()){list.push_back(resId);continue;}
            clear=false;
        }
        if (clear)continue;
        LOG("RENUMBERING CHAIN "+pChain.getName());
        std::cout << pChain.getName()<<" - RENUMBERING "<<std::endl;
        for(size_t iRes=0;iRes < nRes;++iRes)
        {
            MMResidue& pRes=pChain.getResidue(iRes);
            LOG(pRes.getIdentifier()+" TO ID "+std::to_string(iRes+1));
            pRes.setFID((const int &) iRes+1);
        }
    }
}


void toggleSelection(MMResidue& pRes, const double& threshold,
                     const bool& wholeResidue)
{

    protspace::MacroMole& mole=pRes.getParent();
    mole.select(false);
    pRes.select(true);
    const size_t nAtom = pRes.numAtoms();
    const double thres_squared(threshold*threshold);
    for(size_t iAtm=0;iAtm<mole.numAtoms();++iAtm)
    {
        MMAtom& atom = mole.getAtom(iAtm);
        for (size_t iAtmR=0;iAtmR < nAtom;++iAtmR)
        {
            const Coords& pos=pRes.getAtom(iAtmR).pos();
            if (atom.pos().distance_squared(pos)>=thres_squared)continue;
            if (wholeResidue)
                atom.getResidue().select(true);
            else atom.select(true);
        }
    }
}


Coords getCenter(const MMResidue& pRes,const bool& onlyUsed,const double& wHydrogen)
{
    Coords results;
    double nAtm=0.0;
    for (size_t iAtm=0; iAtm< pRes.numAtoms();++iAtm)
    {
        const MMAtom& atom=pRes.getAtom(iAtm);
        if (onlyUsed && !atom.isSelected()) continue;
        if (!wHydrogen && atom.isHydrogen())continue;
        nAtm++;
        results+=atom.pos();
    }
    if (nAtm==0)return results;
    return results/nAtm;

}


void getCounts(const MMResidue& pRes,size_t& nN, size_t& nC, size_t& nO,size_t& nOth)
{
    for(size_t i=0;i<pRes.numAtoms();++i)
    {
        const unsigned char& elem=pRes.getAtom(i).getAtomicNum();
        switch (elem)
        {
        case 6: nC++;break;
        case 7:nN++;break;
        case 8: nO++; break;
        case 1:break;
        default :nOth++;break;
        }
    }
}

void delIntraBond(MMResidue& pRes)
{
    MacroMole& parent=pRes.getParent();
    const size_t nAt(pRes.numAtoms());
    for(size_t iAtom=0;iAtom < nAt;++iAtom)
    {
        MMAtom& atom=pRes.getAtom(iAtom);
        for(size_t iAtomC=iAtom+1;iAtomC < nAt;++iAtomC)
        {
            MMAtom& atomC=pRes.getAtom(iAtomC);
            if (atom.hasBondWith(atomC))
                parent.delBond(atom.getBond(atomC));
        }
    }
}

bool areLinked(const MMResidue &res1, const MMResidue &res2)
{
    const size_t nAt(res1.numAtoms());
    for(size_t iAtom=0;iAtom < nAt;++iAtom)
    {
        const MMAtom& atom=res1.getAtom(iAtom);
        for(size_t iAtomC=0;iAtomC < atom.numBonds();++iAtomC)
        {
            const MMAtom& atomC=atom.getAtom(iAtomC);
           if (&atomC.getResidue()== & res2)return true;
        }
    }
    return false;
}

}
