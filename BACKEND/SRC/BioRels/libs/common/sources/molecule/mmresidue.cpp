
#include <sstream>
#include <algorithm>
#include <fstream>
#include "headers/statics/residuedata.h"
#include "headers/statics/intertypes.h"
#include "headers/molecule/mmresidue.h"
#include "headers/molecule/mmresidue_utils.h"
#include "headers/molecule/macromole.h"
#include "headers/parser/ofstream_utils.h"
#include "headers/statics/strutils.h"
#undef NDEBUG /// Active assertion in release
using namespace protspace;
using namespace std;



MMResidue:: ~MMResidue(){}

MMResidue::MMResidue(MacroMole* const parent):
    ids(0,0),
    mName(""),
    mChain(-2),
    mResType(RESTYPE::UNDEFINED),
    mParent(parent),
    mSelected(true)
{

}

void MMResidue::clearAtoms()
{
    mAtomlist.clear();
}

MMResidue::MMResidue(MacroMole* const parent,
                     const signed char& chain,
                     const std::string& name,
                     const int &fid)
    :ids(0,fid),
      mName(name),
      mChain(chain),
      mResType(RESTYPE::UNDEFINED),
      mParent(parent),
      mSelected(true)
{
    removeAllSpace(mName);
    mAtomlist.reserve(20);

    genIdentifier();

    for(size_t i=0;i<NBAA;++i)
        if (AAcid[i].name==name)mResType=RESTYPE::STANDARD_AA;
}

void MMResidue::genIdentifier()
{
    ostringstream oss;
    oss<<getChain().getName()<<">"<<mName<<":"<<mFId;
    mIdentifier=oss.str();
}


void MMResidue::setName(const std::string& name)
{
    mName=name;
    genIdentifier();

}
const std::string& MMResidue::getMoleName() const
{
    assert(mParent != NULL);
    return mParent->getName();
}

const std::string& MMResidue::getChainName() const
try
{
    assert(mParent != NULL);
    return mParent->getChain(mChain).getName();
}catch(ProtExcept &e)
{
    /// getChain should never fail
    assert(e.getId()!="350901");
    throw;
}



MMChain& MMResidue::getChain()
try{
    assert(mParent != NULL);
    return mParent->getChain(mChain);
}catch(ProtExcept &e)
{
    /// getChain should never fail
    assert(e.getId()!="350901");
    throw;
}




const MMChain& MMResidue::getChain()const
try{
    assert(mParent != NULL);
    return mParent->getChain(mChain);
}catch(ProtExcept &e)
{
    /// getChain should never fail
    assert(e.getId()!="350901");
    throw;
}



void MMResidue::addAtom( MMAtom* const atom) throw(ProtExcept)
{
    if (atom == (MMAtom*)NULL)
        throw_line("320101",
                   "MMResidue::addAtom",
                   "No atom given");
    assert((mParent->isOwner() && &atom->getResidue() == this)
           ||!mParent->isOwner());
    mAtomlist.push_back(atom);
}





void MMResidue::addAtom(MMAtom& atom)
{
    assert((mParent->isOwner() && &atom.getResidue() == this)
           ||!mParent->isOwner());
    mAtomlist.push_back(&atom);

}


void MMResidue::delAtom(MMAtom* const atom) throw(ProtExcept)
{
    if (atom == nullptr)
        throw_line("320201",
                   "MMResidue::delAtom",
                   "No atom given");
    if (&atom->getResidue() != this)
        throw_line("320202",
                   "MMResidue::delAtom",
                   "Given atom is not part of this Residue");

    std::vector<MMAtom*>::iterator itPos=
            std::find(mAtomlist.begin(),mAtomlist.end(),atom);

    if (itPos == mAtomlist.end())
        throw_line("320203",
                   "MMResidue::delAtom",
                   "Given atom is not part of this Residue");
    atom->setResidue(nullptr);
    mAtomlist.erase(itPos);
}



void MMResidue::delAtom(MMAtom& atom)       throw(ProtExcept)
try{

    if (&atom.getResidue() != this)
        throw_line("320301",
                   "MMResidue::delAtom",
                   "Given atom is not part of this Residue");

    const std::vector<MMAtom*>::iterator itPos=
            std::find(mAtomlist.begin(),mAtomlist.end(),&atom);
    if (itPos == mAtomlist.end())
        throw_line("320302",
                   "MMResidue::delAtom",
                   "Given atom is not part of this Residue");

    ///TODO CHECK ISSUE WHEN THIS FUNCTION IS NOT CALLED BY MOLE->DEL ATOM
    atom.setResidue(&mParent->getTempResidue());
    mAtomlist.erase(itPos);
}catch(ProtExcept &e)
{
    /// atom.getResidue shoudl always work.
    assert(e.getId()!="310701");
    throw;
}




MMAtom& MMResidue::getAtom(const size_t& pos)const        throw(ProtExcept)
{
    if (pos >= mAtomlist.size())
        throw_line("320401",
                   "MMResidue::getAtom",
                   "Given position is above the number of atom in this Residue");
    return *mAtomlist.at(pos);

}

const std::string& MMResidue::getIdentifier() const
{
    return mIdentifier;
}


void MMResidue::checkUse()
{
    for (size_t iAtm=0; iAtm< mAtomlist.size();++iAtm)
    {
        const MMAtom& atom=*mAtomlist.at(iAtm);
        if (atom.isSelected()){
            mSelected=true;
            mParent->getChain(mChain).checkSelection();
            return;}
    }
    mSelected=false;
    mParent->getChain(mChain).checkSelection();

}


void MMResidue::select(const bool& isSelected,
                       const bool& applyToChain,
                       const bool& applyToAtom)
{
    mSelected=isSelected;
    if (applyToAtom)
        for (MMAtom* atom:mAtomlist) atom->select(isSelected,false,true);

    if (applyToChain) mParent->getChain(mChain).checkSelection();
}

bool MMResidue::hasAtom(const std::string& pName, size_t& atom)const
{
    for (size_t iAtm=0; iAtm< mAtomlist.size();++iAtm)
    {
        const MMAtom& tatom=*mAtomlist[iAtm];
        if (tatom.getName()!=pName) continue;
        atom=iAtm;
        return true;
    }
    return false;
}


MMAtom& MMResidue::getAtom(const std::string& atomName, bool testLower)const throw(ProtExcept)
{
    for (MMAtom* atom:mAtomlist)
    {
        if (atom->getName()==atomName)return *atom;
    }
    if (!testLower)
        throw_line("320501",
                   "Residue::getAtom","Atom Not found "+atomName+" "+getIdentifier());

    std::string n1(atomName);toLowercase(n1);
    std::string n2;
    for (MMAtom* atom:mAtomlist)
    {
        n2=atom->getName();
        toLowercase(n2);
        if (n1==n2)return *atom;
    }
    throw_line("320502",
               "Residue::getAtom","Atom Not found "+atomName);
}


std::string MMResidue::toString(const bool& wBond)const
{
    ostringstream oss;
    oss << "### RESIDUE : "<< mIdentifier<<"--"
        << "CLASS : "<< getResidueType(*this)<<"--ATOM:"<<mAtomlist.size()<<"\n";
    for (size_t iAtm=0;iAtm < mAtomlist.size();++iAtm)
        oss<<"ATOM|"<<mAtomlist.at(iAtm)->getIdentifier()<<"\t"<<mAtomlist.at(iAtm)->getElement()<<"::"<<mAtomlist.at(iAtm)->getMOL2()<<"\n";
    if (!wBond)return oss.str();
    for (size_t iAtm=0;iAtm < mAtomlist.size();++iAtm)
    {
        const MMAtom& atm = *mAtomlist.at(iAtm);
        for(size_t iBd=0;iBd<atm.numBonds();++iBd)
        {
            const MMBond& bd= atm.getBond(iBd);
            if (&bd.getAtom1()==&atm)oss<<"BOND|"<<bd.toString()<<"\n";
        }
    }
    return oss.str();
}






void MMResidue::serialize(ofstream &out) const
{

    out.write((char*)&mFId,sizeof(mFId));
    // READING ID

    out.write((char*)&mMId,sizeof(mMId));
    size_t length=mName.size();
    out.write((char*)&length,sizeof(size_t));
    out.write(mName.c_str(),mName.size());
    length=mIdentifier.size();
    out.write((char*)&length,sizeof(size_t));
    out.write(mIdentifier.c_str(),mIdentifier.size());


    out.write((char*)(&mChain),sizeof(signed char));

    out.write((char*)(&mResType),sizeof(uint16_t));

    out.write((char*)(&mSelected),sizeof(bool));

     length = mAtomlist.size();
    out.write((char*)&length,sizeof(size_t));

    int val;
    for(size_t i=0;i< mAtomlist.size();++i)
    {

        val=mAtomlist.at(i)->getMID();
        out.write((char*)&val,sizeof(mMId));
    }




}


void MMResidue::unserialize(ifstream &ifs)
{
    // READING ID of molecule
    ifs.read((char*)&mFId,sizeof(int));
    ifs.read((char*)&mMId,sizeof(int));

    size_t length=0;
    readSerializedString(ifs,mName);
    readSerializedString(ifs,mIdentifier);

    ifs.read((char*)&mChain,sizeof(signed char));
    ifs.read((char*)&mResType,sizeof(uint16_t));

    ifs.read((char*)&mSelected,sizeof(bool));

    ifs.read((char*)&length,sizeof(size_t));
    int pos=0;
    for(size_t i=0;i< length;++i)
    {
        ifs.read((char*)&pos,sizeof(int));

        mAtomlist.push_back(&mParent->getAtom((const size_t &) pos));
    }

}



bool MMResidue::getAtom(const std::string &pAtomName, size_t &pos) const {
    for ( pos=0; pos< mAtomlist.size();++pos)
    {
        MMAtom& atom=*mAtomlist.at(pos);
        if (atom.getName()==pAtomName)    return true;

    }return false;
}

size_t MMResidue::numHeavyAtom() const {
    size_t nAtm=0;
    for(const MMAtom* atom:mAtomlist)
        if (atom->getAtomicNum()>1)nAtm++;
    return nAtm;
}


