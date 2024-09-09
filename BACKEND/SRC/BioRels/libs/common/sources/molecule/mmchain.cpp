#include <algorithm>
#include <fstream>
#undef NDEBUG /// Active assertion in release
#include <assert.h>
#include "headers/molecule/mmchain.h"
#include "headers/molecule/mmresidue.h"
#include "headers/molecule/macromole.h"
#include "headers/statics/intertypes.h"
#include "headers/molecule/mmresidue_utils.h"
using namespace protspace;


MMChain::MMChain(MacroMole& molecule,
                 const std::string& name):
    mMolecule(&molecule),
    mName(name),
    mSelected(true),
    mChainType(CHAINTYPE::UNDEFINED)
{
}




MMChain::MMChain(MacroMole* const molecule,
                 const std::string& name):
    mMolecule(molecule),
    mName(name),
    mChainType(CHAINTYPE::UNDEFINED)
{
    assert(mMolecule != (MacroMole*)NULL);

}



const std::string& MMChain::getMoleName() const
{
    assert(mMolecule != (MacroMole*)NULL);
    return mMolecule->getName();
}

void MMChain::addResidue(MMResidue* const residue) throw(ProtExcept)
{
    if (residue == (MMResidue*)NULL)
        throw_line("330101",
                         "MMChain::addResidue",
                         "No Residue given");
    if (&residue->getChain() != this)
        throw_line("330102",
                         "MMChain::addResidue",
                         "Given residue not part of this chain");

    mResiduelist.push_back(residue);
}

void MMChain::addResidue(MMResidue& residue)
{
    if(&residue.getChain() != this)
        throw_line("330201",
                         "MMChain::addResidue",
                         "Given residue not part of this chain");
    mResiduelist.push_back(&residue);

}

void MMChain::delResidue(MMResidue* const residue) throw(ProtExcept)
{
    if (residue == (MMResidue*)NULL)
        throw_line("330301",
                         "MMChain::delResidue",
                         "No Residue given");
    if (&residue->getChain() != this)
        throw_line("330302",
                         "MMChain::delResidue",
                         "Given Residue is not part of this chain");

/// Finding the MMResidue in the list
    std::vector<MMResidue*>::iterator itPos=
            std::find(mResiduelist.begin(),mResiduelist.end(), residue);
    if (itPos == mResiduelist.end())
        throw_line("330203",
                         "MMChain::delResidue",
                         "Given Residue is not part of this chain");

/// Unsetting the link between this MMResidue and this chain
    residue->setChain(-1);
    mResiduelist.erase(itPos);
}







void MMChain::delResidue(MMResidue&       residue) throw(ProtExcept)
{
    if (&residue.getChain() != this)
        throw_line("330401",
                         "MMChain::delResidue",
                         "Given Residue is not part of this chain");
    std::vector<MMResidue*>::iterator itPos=
            std::find(mResiduelist.begin(),mResiduelist.end(), &residue);
    if (itPos == mResiduelist.end())
        throw_line("330402",
                         "MMChain::delResidue",
                         "Given Residue is not part of this chain");
    residue.setChain(-1);
    mResiduelist.erase(itPos);
}








MMResidue& MMChain::getResidue(const size_t& pos) throw(ProtExcept)
{
    if (pos >= mResiduelist.size())
        throw_line("330501",
                         "MMChain::getResidue",
                         "Given position is above the number of Residues");
return *mResiduelist.at(pos);
}






const MMResidue& MMChain::getResidue(const size_t& pos)const throw(ProtExcept)
{
    if (pos >= mResiduelist.size())
        throw_line("330601",
                         "MMChain::getResidue",
                         "Given position is above the number of Residues");
return *mResiduelist.at(pos);
}




MMResidue& MMChain::getResidueByFID(const int& pos) const throw(ProtExcept)
{
  if (pos< (int)mResiduelist.size())
  {
      if (mResiduelist.at((unsigned int) pos)->getFID() == pos)
          return *mResiduelist.at((unsigned int) pos);
  }
  for(size_t i=0;i< mResiduelist.size();++i)
  {
      if (mResiduelist.at(i)->getFID()==pos)return *mResiduelist.at(i);
  }
  throw_line("330701",
                   "MMChain::getResidueByFID",
                   "Given position  has not been found");
}





MMResidue& MMChain::getResidue(const std::string& name,
                               const int &num,
                               const bool& name_1_letter) throw(ProtExcept)
{
    const std::string& name3=(name_1_letter)?residue1Lto3L(name):name;
    for (size_t iRes=0; iRes< mResiduelist.size();++iRes)
    {
         MMResidue& residue=*mResiduelist.at(iRes);

        if (residue.getName()==name3 &&
           ((residue.getFID()==num && num != -1)||num==-1)

                )return residue;

    }
    throw_line("330801",
                     "MMChain::getResidue",
                     "No Residue found with the given parameters");
}



const MMResidue& MMChain::getResidue(const std::string& name,
                               const int &num,
                               const bool& name_1_letter) const throw(ProtExcept)
{
    const std::string& name3=(name_1_letter)?residue1Lto3L(name):name;
    for (size_t iRes=0; iRes< mResiduelist.size();++iRes)
    {
         const MMResidue& residue=*mResiduelist.at(iRes);

        if (residue.getName()==name3 &&
           ((residue.getFID()==num && num != -1)||num==-1)

                )return residue;

    }
    throw_line("330901",
                     "MMChain::getResidue",
                     "No Residue found with the given parameters");
}





size_t MMChain::getResiduePos(const MMResidue& res)const
{
    std::vector<MMResidue*>::const_iterator itP=
           std::find(mResiduelist.begin(),
                 mResiduelist.end(),
                 &res);
    if (itP==mResiduelist.end()) return -1;
    return std::distance(mResiduelist.begin(),itP);
}







size_t MMChain::numSelectedResidue() const
{
    size_t n=0;
    for (size_t iRes=0;iRes < mResiduelist.size();++iRes)
    {
        const MMResidue& residue = *mResiduelist.at(iRes);
        if (residue.isSelected())n++;
    }
    return n;
}




std::string MMChain::toString()const
{
    return mMolecule->getName()+"_"+mName;
}



void MMChain::select(const bool& isUsed,
            const bool& applyToRes)
{
    mSelected=isUsed;
    if (!applyToRes)return;
    for (size_t iRes=0;iRes < mResiduelist.size();++iRes)
    {
        MMResidue& residue = *mResiduelist.at(iRes);
        residue.select(mSelected,false,true);
    }
}



void MMChain::checkSelection()
{
    for (size_t iRes=0; iRes< mResiduelist.size();++iRes)
    {
        const MMResidue& residue=*mResiduelist.at(iRes);
        if (residue.isSelected()){mSelected=true;return;}
    }
    mSelected=false;
}


void MMChain::serialize(std::ofstream& out)const
{

    size_t length=mName.size();
    out.write((char*)&length,sizeof(size_t));

    out.write(mName.c_str(),mName.size());

    length=mResiduelist.size();

    out.write((char*)&length,sizeof(size_t));

    for(size_t i=0;i< mResiduelist.size();++i)
    {
        out.write((char*)&mResiduelist.at(i)->getMID(),sizeof(int));
    }

    out.write((char*)(&mSelected),sizeof(bool));

    out.write((char*)(&mChainType),sizeof(uint16_t));
}



void MMChain::unserialize(std::ifstream& ifs)
{
    size_t length=0;
    ifs.read((char*)&length,sizeof(size_t));
    char* temp = new char[length+1];
    ifs.read(temp,length);
    temp[length]='\0';
    mName=temp;
    delete[]temp;


    ifs.read((char*)&length,sizeof(size_t));
    int pos=0;
    for(size_t i=0;i< length;++i)
    {
         ifs.read((char*)&pos,sizeof(int));
         mResiduelist.push_back(&mMolecule->getResidue(pos));
    }

    ifs.read((char*)&mSelected,sizeof(bool));
    ifs.read((char*)(&mChainType),sizeof(uint16_t));
}


size_t MMChain::getResiduePos(const std::string& pName, const int &num,
                     const bool& name_1_letter) const
try{
    const std::string& name3=(name_1_letter)?residue1Lto3L(pName):pName;
    for (size_t iRes=0; iRes< mResiduelist.size();++iRes)
    {
         const MMResidue& residue=*mResiduelist.at(iRes);

        if (residue.getName()==name3 &&
           ((residue.getFID()==num && num != -1)||num==-1)

                )return iRes;

    }
    return mResiduelist.size();
}catch(ProtExcept &e)
{
    e.addHierarchy("MMChain::getResiduePos");
    e.addDescription("Name : "+pName);
    throw;
}
