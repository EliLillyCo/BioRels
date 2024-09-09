#include <sstream>
#include <fstream>
#include "headers/molecule/mmbond.h"
#include "headers/molecule/mmbond_utils.h"
#include "headers/statics/intertypes.h"
#undef NDEBUG /// Active assertion in release
using namespace std;
using namespace protspace;

MMBond::~MMBond(){}
MMBond::MMBond(const MacroMole& parent,
               MMAtom &atom1,
               MMAtom &atom2,
               const int &mid,
               const uint16_t &bondType)
throw(ProtExcept)
    :protspace::link<MacroMole,MMAtom >(parent,mid,-1,atom1,atom2),
      mType(bondType),
      mSelected(true)
{
    if (&mDot1==&mDot2)
        throw_line("300101",
                   "MMBond::MMBond",
                   "Both atom are the same");


}





MMAtom& MMBond::getOtherAtom(MMAtom* const atom) const throw(ProtExcept)
{
    if (atom== nullptr)
        throw_line("300201",
                   "MMBond::getOtherAtom",
                   "Given atom is NULL");
    return getOther(*atom);


}
\
double MMBond::dist() const
{
    return mDot1.pos().distance(mDot2.pos());
}


void MMBond::setUse(const bool& isUsed, const bool& applyToAtom)
{
    mSelected=isUsed;
    if (!applyToAtom)return;
    mDot1.select(mSelected,true,false);
    mDot2.select(mSelected,true,false);

}


void MMBond::serialize(std::ofstream& out) const
{
    out.write((char*)&mType,sizeof(uint16_t));
    out.write((char*)&mMId,sizeof(int));
    out.write((char*)&mFId,sizeof(int));
    out.write((char*)&mDot1.getMID(),sizeof(int));
    out.write((char*)&mDot2.getMID(),sizeof(int));
    out.write((char*)&mSelected,sizeof(bool));
}

protspace::MMBond::operator std::string()
{
    return "";
}

std::string protspace::MMBond::toString()const
{
    std::ostringstream oss;
     oss<<"|>Bond("<< mMId
        <<"/"      <<mFId
        <<") :"    <<mDot1.getIdentifier()
        <<"<->"    <<mDot2.getIdentifier()
        <<" "<<((mSelected)?"T":"F")
        <<"\t"     <<protspace::getBondType(*this);
     return oss.str();
}
