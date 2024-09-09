#ifndef INTEROBJ_H
#define INTEROBJ_H

#include "headers/molecule/mmatom.h"

namespace protspace
{
class MMRing;



class InterObj
{
friend class InterData;


protected:

    ///
    /// \brief First atom involved in the interaction
    ///
    /// In the case of an aromatic ring the mAtom1 will be the dummy atom
    /// describing the mass center of the ring
    ///
    MMAtom* mAtom1;

    ///
    /// \brief Second atom involved in the interaction
    ///
    /// In the case of an aromatic ring the mAtom2 will be the dummy atom
    /// describing the mass center of the ring
    ///
    MMAtom* mAtom2;


    ///
    /// \brief Type of the interaction. See namespace INTER for more types
    ///
    unsigned char mType;


    ///
    /// \brief Distance found between the two atoms in interaction
    ///
    double mDistance;


    ///
    /// \brief Angle (when applied) of the interaction
    ///
    double mAngle;


    ///
    /// \brief Tell whether the involved atom are selected
    ///
    bool mIsUsed;


    ///
    /// \brief Ring system represented by mAtom1 (when applied)
    ///
    const MMRing* mRing1;


    ///
    /// \brief Ring system represented by mAtom1 (when applied)
    ///
    const MMRing* mRing2;

    /**
   * @brief Used for some interactions, specify the orientation of the interaction
   * Used for HBond, weak HBond, Ionic and Pi Cation, tell whether
   * the Hydrogen or the Cationic atom comes from the protein side or the ligand side.
   * 1 is Ligand side, 2 is Protein side, 0 is unrelated
   */
    unsigned char mIsProtSide;

    unsigned int mDBID;

public:
    InterObj(MMAtom& atom1,
             MMAtom& atom2,
             const unsigned char & type,
             const double& distance);
    InterObj(const InterObj& p);
    InterObj& operator=(const InterObj& other);

    std::string toString(const bool &forCSV=false)const;


    bool  operator ==(const InterObj& inter);

    /**
   * @brief Convert the interaction type into a human readable name
   * @return String describing the type
   */
    const std::string &interToString()const;

    const unsigned char& getIsProtSide() const;
    void setIsProtSide(const unsigned char& value);
    const double& getAngle() const;
    void setAngle(const double &value);

    void setRing1(const MMRing& ring){mRing1=&ring;}
    void setRing2(const MMRing& ring){mRing2=&ring;}
    MMResidue& getResidue1()const;
    MMResidue& getResidue2()const;
    MMAtom& getAtom1()const {return *mAtom1;}
    MMAtom& getAtom2()const {return *mAtom2;}
    const MMRing* getRing1()const {return mRing1;}
    const MMRing* getRing2()const {return mRing2;}
    const double& getDistance()const {return mDistance;}
    const unsigned char & getType()const {return mType;}
    void setUse(const bool& use){mIsUsed=use;}
    void setDBID(const unsigned int& id){mDBID=id;}
    const unsigned int& getDBID()const{return mDBID;}
};
struct less_than_inter
{
    inline bool operator() (const InterObj& struct1, const InterObj& struct2)
    {
        if (struct1.getAtom1().getMID() >= struct2.getAtom1().getMID())return false;
        if (struct1.getAtom2().getMID() >= struct2.getAtom2().getMID())return false;
        if (struct1.getType()           >= struct2.getType())          return false;
        if (struct1.getDistance()       >= struct2.getDistance())      return false;
        return true;
    }
};
}
#endif // INTEROBJ_H

